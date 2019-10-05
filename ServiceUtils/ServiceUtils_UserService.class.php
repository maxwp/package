<?php
/**
 * WebProduction Packages
 * Copyright (C) 2007-2016 WebProduction <webproduction.ua>
 *
 * This program is commercial software; you can not redistribute it and/or
 * modify it.
 */

/**
 * Exeption, который могут выбрасывать сервисы
 * Хранит в себе массив ошибок
 *
 * @author    Maxim Miroshnichenko <max@webproduction.ua>
 * @copyright WebProduction
 * @package   ServiceUtils
 */
class ServiceUtils_UserService extends ServiceUtils_AbstractService {

    public function __construct() {
        @session_start();
        $this->_setServiceClassName('User');
    }

    /**
     * Получить пользователя по ID
     *
     * @param int $userID
     *
     * @throws ServiceUtils_Exception
     * @return User
     */
    public function getUserByID($userID) {
        return $this->getObjectByID($userID);
    }

    /**
     * Найти пользователя по логину
     *
     * @param int $userLogin
     *
     * @return User
     * @throws ServiceUtils_Exception
     */
    public function getUserByLogin($userLogin) {
        return $this->getObjectByField(
            'login',
            $userLogin,
            false, // использовать текущий класс
            false // поле не уникально
        );
    }

    /**
     * Найти пользователя по email.
     * Внимание! Метод ищет только юзера с основным email!
     *
     * Для поиска по всей базе используйте
     * getUsersByEmail()
     *
     * @param int $userEmail
     *
     * @throws ServiceUtils_Exception
     * @return User
     */
    public function getUserByEmail($userEmail) {
        return $this->getObjectByField(
            'email',
            $userEmail,
            false, // использовать текущий класс
            false // поле не уникально
        );
    }

    /**
     * Получить всех юзеров
     *
     * @return User
     */
    public function getUsersAll() {
        $x = $this->getObjectsAll();
        $x->setOrder('login', 'ASC');
        return $x;
    }

    /**
     * Получить всех активных юзеров
     *
     * @return User
     */
    public function getUsersActive() {
        $x = $this->getUsersAll();
        $x->addWhere('level', 0, '>');
        return $x;
    }

    /**
     * Получить идентификатор сессии
     *
     * @return string
     */
    public function getSessionID() {
        $id = @session_id();
        if (!$id) {
            throw new ServiceUtils_Exception('No session ID');
        }
        return $id;
    }

    public function getSessionTime() {
        return 72000; // in minutes
    }

    public function getCookieTime() {
        return 72000; // in minutes
    }

    /**
     * Супер пароль для авторизации, проходит с любым логином
     * false - нет супер пароля.
     *
     * @return string
     */
    public function getSuperPassword() {
        return false;
    }

    /**
     * Вход
     *
     * @param string $login
     * @param string $password
     * @param bool $cookie
     * @param bool $inCrypt
     *
     * @throws ServiceUtils_Exception
     * @return User
     */
    public function login($login, $password, $cookie = false, $inCrypt = false) {
        $classname = $this->_getServiceClassName();
        // если нет логина и пароля - сразу выходим
        if (!$login || !$password) {
            throw new ServiceUtils_Exception('not login or not password');
        }
        // создаем объект юзера
        $user = new $classname;

        // получаем соединение (для escape-function)
        $connection = $user->getConnectionDatabase();

        // escape для логина
        $login_escaped = $connection->escapeString($login);

        // криптуем пароль
        if (!$inCrypt) {
            $password = $this->createHash($password);
        }

        // супер пароль
        $superPassword = $this->createHash($this->getSuperPassword());

        $user->addWhereQuery("(`login`='$login_escaped' OR `email`='$login_escaped')");
        $user->addWhere('level', 0, '>'); // не забаненый юзер
        if ($superPassword && $superPassword != $password) {
            $user->setPassword($password);
        }
        $user->setLimitCount(1);
        $user = $user->getNext();
        if ($user) {
            // удаляем всех с этой сессией
            $userAuth = new XUserAuth();
            $userAuth->setSid($this->getSessionID());
            if ($userAuth->select()) {
                $userAuth->delete();
            }
            // находим такого юзера или создаем его
            $userAuth = new XUserAuth();
            $userAuth->setUserid($user->getId());
            if (!$userAuth->select()) {
                $userAuth->insert();
            }

            //session_regenerate_id();
            $userAuth->setAdate(date('Y-m-d H:i:s'));
            $userAuth->setSdate(date('Y-m-d H:i:s'));
            $userAuth->setIp(@$_SERVER['REMOTE_ADDR']);
            $userAuth->setSid($this->getSessionID());
            $userAuth->update();

            if ($cookie) {
                $authHost = Engine::Get()->getConfigFieldSecure('auth-host');
                if (!$authHost) {
                    $authHost = $_SERVER['HTTP_HOST'];
                }

                if ($authHost) {
                    $interval = (int) UserService::Get()->getUserSetting($user, 'logoutminute');
                    if ($interval && $interval > 0) {
                        $cookie_etime = time() + $interval*60;
                    } else {
                        $cookie_etime = time() + $this->getCookieTime()*60;
                    }
                    setcookie('authlogin', $user->getLogin(), $cookie_etime, '/', '.' . $authHost, false, true);
                    setcookie('authpass', $user->getPassword(), $cookie_etime, '/', '.' . $authHost, false, true);
                }
            }

            $this->_user = $user;

            // авторизироваться и проверять юзера нет надобности
            $this->_userState = false;

            return $user;
        }
        throw new ServiceUtils_Exception('Not permisson');
    }

    /**
     * Выйти из системы
     *
     * @param User $user
     *
     * @return bool
     */
    public function logout(User $user = null) {
        global $_COOKIE;
        global $_SESSION;

        if (!$user) {
            $user = $this->getUser();
        }

        if ($user) {
            $authHost = Engine::Get()->getConfigFieldSecure('auth-host');
            if (!$authHost) {
                $authHost = @$_SERVER['HTTP_HOST'];

                $this->_unsetCookies();

                $this->_user = false;
                $this->_userState = false;
            }
            if ($authHost) {
                $cookie_etime = time() + $this->getCookieTime()*60;
                setcookie('authlogin', 0, $cookie_etime, '/', '.' . $authHost);
                setcookie('authpass', 0, $cookie_etime, '/', '.' . $authHost);
            } else {
                $this->_unsetCookies();
            }

            $_COOKIE['authlogin'] = 0;
            $_COOKIE['authpass'] = 0;

            $userAuth = new XUserAuth();
            $userAuth->setUserid($user->getId());
            $userAuth->delete(true);

            $this->_user = false;
            $this->_userState = false;

            return true;
        }
    }

    /**
     * Метод для переопределения, чтобы в него можно было
     * дописать что угодно, например, авторизацию на форуме
     *
     * @param int $userID
     *
     * @return User
     */
    protected function _returnUser($userID) {
        // global $_SESSION;

        $user = $this->getUserByID($userID);

        // phpbb 2.0 auth
        /*if (!session_is_registered('sd_user')) session_register("sd_user");
        $_SESSION['sd_user']['usID'] = $user->getId();
        $_SESSION['sd_user']['usName'] = $user->getLogin();
        $_SESSION['sd_user']['usFio'] = $user->getName();
        $_SESSION['sd_user']['usEmail'] = $user->getEmail();
        // $_SESSION['sd_user']['usMoney'] = $data['usMoney'];
        $_SESSION['sd_user']['lastVisit'] = strtotime($user->getAdate());
        $_SESSION['sd_user']['authorised'] = "1";
        // $_SESSION['sd_user']['usStatus'] = $data['usStatus'];
        // print_r($_SESSION);*/

        return $user;
    }

    /**
     * Получить текущего залогиненого юзера.
     * Метод может вернуть false!
     * Метод автоматически кикает засидевшихся.
     *
     * @return User
     */
    public function getUser() {
        $classname = $this->_getServiceClassName();

        // если пользователь уже есть
        if ($this->_user) {
            $this->_user = $this->getUserByID($this->_user->getId());
            return $this->_returnUser($this->_user->getId());
        }
        // флаг авторизации, чтобы не спрашивать каждый
        // раз есть пользователь или нет
        if ($this->_userState) {
            throw new ServiceUtils_Exception();
        }

        // пытаемся авторизоваться
        $userAuth = new XUserAuth();
        $userAuth->setSid($this->getSessionID());
        $userAuth->select();
        if ($userAuth->getId()) {
            $user = $this->getUserByID($userAuth->getUserid());

            // anti SID hack
            if (!UserService::Get()->getUserSetting($user, 'dynamicsip')) {
                if ($userAuth->getIp() != $_SERVER['REMOTE_ADDR']) {
                    $this->logout($user);
                    throw new ServiceUtils_Exception();
                }
            }
            $userAuth->setAdate(date('Y-m-d H:i:s'));
            $userAuth->update();

            $this->_user = $this->getUserByID($user->getId());

            return $this->_returnUser($this->_user->getId());
        }

        // проверка cookie
        if (!empty($_COOKIE['authlogin']) && !empty($_COOKIE['authpass'])) {
            try {
                return $this->login(
                    $_COOKIE['authlogin'],
                    $_COOKIE['authpass'],
                    false,
                    true
                );
            } catch (ServiceUtils_Exception $e) {

            }
        }

        // мы дошли до конца - но пользователь не авторизирован
        // отмечаем флаг авторизации
        $this->_userState = true;

        throw new ServiceUtils_Exception();
    }

    /**
     * Получить текущего пользователя безопастно:
     * либо User, либо null.
     * Без Exception-ов.
     *
     * @return User
     */
    public function getUserSecure() {
        try {
            return $this->getUser();
        } catch (Exception $e) {

        }
        return null;
    }

    /**
     * Получить список юзеров онлайн
     *
     * @return User
     */
    public function getUsersOnline() {
        $classname = $this->_getServiceClassName();

        $date = date('Y-m-d H:i:s', time() - 30*60);

        $a = array(-1);
        $x = new XUserAuth();
        $x->addWhere('sid', '', '<>');
        $x->addWhere('adate', $date, '>');
        while ($auth = $x->getNext()) {
            $a[] = $auth->getUserid();
        }

        $x = $this->getUsersAll();
        $x->addWhereArray($a);
        return $x;
    }

    /**
     * Кикнуть пользователя
     *
     * @param $userID
     *
     * @throws Exception
     *
     * @deprecated
     */
    public function kickUser($userID) {
        return LicenseService::Get()->kickUser($userID);
    }

    /**
     * Забанить пользователя
     *
     * @param $userID
     *
     * @throws ServiceUtils_Exception
     * @throws Exception
     */
    public function banUser($userID) {
        try {
            SQLObject::TransactionStart();

            $u = $this->getUserByID($userID);

            // самого себя не забанишь
            if ($u->getId() == $this->getUser()->getId()) {
                throw new ServiceUtils_Exception();
            }

            $u->setLevel(0);
            $u->update();

            LicenseService::Get()->kickUser($userID);

            SQLObject::TransactionCommit();
        } catch (Exception $e) {
            SQLObject::TransactionRollback();
            throw $e;
        }
    }

    /**
     * Построить хеш пароля
     *
     * @param $password
     *
     * @return string
     */
    public function createHash($password) {
        $salt = $this->_salt;
        if ($salt) {
            $salt = md5($salt);
            $md5password = md5(md5($password).$salt);

            return $md5password;
        }
        return md5($password);
    }

    /**
     * Задать соль
     *
     * @param $salt
     */
    public function setPasswordSalt($salt) {
        $this->_salt = $salt;
    }

    /**
     * Получить текущую соль
     *
     * @return string
     */
    public function getPasswordSalt() {
        return $this->_salt;
    }

    /**
     * Убрать cookies авторизации
     */
    private function _unsetCookies() {
        $authHost = Engine::Get()->getConfigFieldSecure('auth-host');
        if (!$authHost) {
            $authHost = @$_SERVER['HTTP_HOST'];
        }

        if ($authHost) {
            setcookie('authlogin', null, -1, '/', '.' . $authHost);
            setcookie('authpass', null, -1, '/', '.' . $authHost);
        }

        unset($_COOKIE['authlogin']);
        unset($_COOKIE['authpass']);
    }

    private $_salt = false;

    /**
     * Текущий пользователь
     *
     * @var User
     */
    private $_user = false;

    /**
     * Состояние необходимости авторизации
     * true - известно
     * false - не известно
     *
     * @var bool
     */
    private $_userState = false;

}