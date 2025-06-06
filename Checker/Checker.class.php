<?php

/**
 * Eventic Packages
 * Copyright (C) 2007-2025 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Класс-проверщик различных данных на корректность
 */
class Checker {

    /**
     * Проверить E-mail на корректность
     *
     * @param string $email
     *
     * @return bool
     */
    public static function CheckEmail($email) {
        // если есть две точки - сразу false
        if (substr_count($email, '..')) {
            return false;
        }

        if (preg_match('/^([0-9a-z^\-\.\+\_]+)@(([0-9a-z\-\_]+\.)+)([a-z]{2,})$/i', $email, $r)) {
            // email подошел под шаблон name@domain
            // разбиваем весь домен и name по точке
            // и проверяем каждый фрагмент как хост
            $domainArray = explode('.', $r[2] . $r[4]);
            foreach ($domainArray as $name) {
                if (!self::CheckHostname($name)) {
                    return false;
                }
            }

            // проверяем name@
            $name = $r[1];
            $result = false;

            // имя может соответсововать "localhost" например
            if (preg_match('/^([\.a-z0-9-\_\+]+)$/i', $name)) {
                $result = true;
            } elseif (preg_match('/^([a-z0-9\.\+\-\_]+)\.([0-9a-z\-\.]{1,})$/i', $name)) {
                $result = true;
            }

            if (!$result) {
                return false;
            }

            // дополнительно проверяем, чтобы хост не начинался на дефис
            // issue #66088 - есть емейлы, которые могут начинаться на дефис
            // if (preg_match("/^-/i", $name)) return false;
            // дополнительно проверяем, чтобы хост не заканчивался на дефис
            // issue #66088 - есть емейлы, которые могут заканчиваться на дефис
            // if (preg_match("/-$/i", $name)) return false;
            // дополнительно проверяем, чтобы хост не содержал несколько дефисов подряд
            // issue #25443 - есть емейлы, которые могу содержать несколько дефисов!
            // if (preg_match("/([-]{2,})/i", $hostname)) return false;
            // дополнительно проверяем, чтобы хост не начинался на точку
            if (preg_match("/^\./i", $name))
                return false;

            // дополнительно проверяем, чтобы хост не заканчивался на точку
            if (preg_match("/\.$/i", $name))
                return false;

            // дополнительно проверяем, чтобы хост не содержал несколько точек подряд
            if (preg_match("/([\.]{2,})/i", $name))
                return false;

            // все ок
            return true;
        }
        return false;
    }

    /**
     * Проверить номер icq на корректность ввода
     *
     * @param mixed $icq
     * @deprecated
     * @return bool
     */
    public static function CheckICQ($icq) {
        return (bool) preg_match('/^[0-9]{6,9}$/i', $icq);
    }

    /**
     * Проверить логин
     *
     * @param string $login
     * @param int $minLength
     * @param string $allowed
     *
     * @return bool
     */
    public static function CheckLogin($login, $minLength = 3, $allowed = 'auto') {
        // минимальная длинна логина - 3 символа
        if ($minLength <= 0) {
            $minLength = 3;
        }
        if ($allowed == 'auto') {
            $allowed = '@.'; // разрешено использовать email в качестве логина
        }
        // экранируем
        if ($allowed) {
            $allowed = preg_quote($allowed);
        }
        return (bool) preg_match('/^[a-z0-9'.$allowed.']{'.$minLength.',}$/i', $login);
    }

    /**
     * Проверить пароль на корректность
     *
     * @param string $password
     * @param int $min
     * @param int $max
     *
     * @return bool
     */
    public static function CheckPassword($password, $min = 6, $max = false) {
        $f = 'mb_strlen';
        if (!function_exists($f))
            $f = 'strlen';
        if ($min && $f($password) < $min) {
            return false;
        }
        if ($max && $f($password) > $max) {
            return false;
        }
        if (!$password && $min > 0) {
            return false;
        }
        $strongOfPassword = 0;
        //Проверяем наличие чисел
        if (preg_match("/([0-9]+)/", $password)) {
            $strongOfPassword++;
        }
        //Проверяем наличие больших букв
        if (preg_match("/([A-Z]+)/", $password)) {
            $strongOfPassword++;
        }
        //Проверяем наличие маленьких букв
        if (preg_match("/([a-z]+)/", $password)) {
            $strongOfPassword++;
        }
        //Проверяем наличие спецсимволов
        if (preg_match("/\W/", $password)) {
            $strongOfPassword++;
        }
        if ($strongOfPassword < 2) {
            return false;
        }

        return true;
    }

    /**
     * Проверить Фамилию+имя+отчество на корректность ввода.
     * Допустимы:
     * Фамилия Имя Отчество
     * Фамилия И.О.
     * Фамилия И. О.
     * Фамилия И О
     *
     * @param string $name
     *
     * @return bool
     */
    public static function CheckName($name) {
        if (preg_match("/([0-9]+)/ius", $name)) {
            // если есть цифры - болт
            return false;
        }
        return ((bool) preg_match('/^(.+?)\s+(.+?)\s+(.+?)$/ius', $name) ||
                (bool) preg_match('/^(.+?)\s+(.+?)\.\s*(.+?)\.$/ius', $name));
    }

    /**
     * Проверить номер телефона на корректность ввода
     *
     * @param string $phone
     *
     * @author Ramm
     *
     * @return bool
     */
    public static function CheckPhone($phone) {
        if (!preg_match('/^[0-9\-\+\(\)\ ]+$/', $phone)) {
            return false;
        }

        $length = strlen(preg_replace('/\D/', '', $phone));
        if ($length < 2 || $length > 13)
            return false;

        return true;
    }

    /**
     * Проверить дату на корректность ввода
     *
     * @param int $datetime
     *
     * @return bool
     */
    public static function CheckDate($datetime) {
        if (!$datetime) {
            return false;
        }

        $x = @strtotime($datetime);

        // если дата меньше 1970 - false
        if (!$x || $x < '-2211759589') {
            return false;
        }
        return true;
    }

    /**
     * Проверить доменное имя на корректность ввода
     *
     * @param string $domainname
     * @param bool $cyrillic
     *
     * @author Max
     *
     * @return bool
     */
    public static function CheckDomainName($domainname, $cyrillic = false) {
        // в целом - все должно соответствовать хосту
        if (!self::CheckHostname($domainname, $cyrillic)) {
            return false;
        }

        // если начинается на www - то уже не правильно
        if (preg_match("/^www\./i", $domainname)) {
            return false;
        }

        return true;
    }

    /**
     * Проверить URL на корректность ввода
     *
     * @param string $url
     *
     * @return bool
     */
    public static function CheckURL($url) {
        $pattern = '/^(?:(https|http|ftp):\/\/)?(?:(\w+)(?::([\w\d]+))?@)?(?:www\.)?([^:]+?)(?::(\d+))?(\/.*)*\/?$/si';
        if (!preg_match($pattern, $url, $matches)) {
            return false;
        }

        // @todo без собак
        return self::CheckIP(@$matches[4]) ? true : self::CheckHostname(@$matches[4]);
    }

    /**
     * Проверить URL или host без wrapper'a на корректность
     *
     * @param string $hostname
     * @param bool $cyrillic
     *
     * @author Ramm
     * @author Max
     *
     * @return bool
     */
    public static function CheckHostname($hostname, $cyrillic = false) {
        $result = false;

        if ($cyrillic) {
            // имя может соответсововать "localhost" например
            if (preg_match('/^([a-z0-9-а-яіїєґ]+)$/iu', $hostname)) {
                $result = true;
            } elseif (preg_match('/^([a-z0-9а-яіїєґ\.\-\_]+)\.([a-z\.]{2,})$/iu', $hostname)) {
                $result = true;
            }
        } else {
            // имя может соответсововать "localhost" например
            if (preg_match('/^([a-z0-9-\_]+)$/i', $hostname)) {
                $result = true;
            } elseif (preg_match('/^([a-z0-9\.\-\_]+)\.([a-z\.]{2,})$/i', $hostname)) {
                $result = true;
            }
        }

        if (!$result) {
            return false;
        }

        // дополнительно проверяем, чтобы хост не начинался на дефис
        if (preg_match("/^-/i", $hostname))
            return false;

        // дополнительно проверяем, чтобы хост не заканчивался на дефис
        if (preg_match("/-$/i", $hostname))
            return false;

        // дополнительно проверяем, чтобы хост не содержал несколько дефисов подряд
        if (preg_match("/([-]{2,})/i", $hostname))
            return false;

        // дополнительно проверяем, чтобы хост не начинался на точку
        if (preg_match("/^\./i", $hostname))
            return false;

        // дополнительно проверяем, чтобы хост не содержал несколько точек подряд
        if (preg_match("/([\.]{2,})/i", $hostname))
            return false;

        return true;
    }

    /**
     * Проверить IP на корректность ввода.
     * По умолчанию формат IPv4
     * дополнительно можно указать IPv6
     *
     * @param int $ip
     *
     * @author idea of atarget
     *
     * @return bool
     */
    public static function CheckIP($ip, $format = 'ipv4') {
        if ($format == 'ipv4') {
            $x = @long2ip(@ip2long($ip));
            if ($x == $ip) {
                return true;
            }
        } elseif ($format == 'ipv6') {
            if (preg_match('/((^|:)([0-9a-fA-F]{0,4})){1,8}$/', $ip)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Проверить картинку на допустимость формата.
     * Нужно указывать полный пусть к картине или полный URL
     *
     * @param string $filepath
     * @param array $allowFormats
     *
     * @todo idea: проверка на размеры и пропорции
     *
     * @return bool
     */
    public static function CheckImageFormat($filepath, $allowMIME = array(
        'image/gif', 'image/jpeg', 'image/png', 'image/pjpeg')) {
        $sizeArray = @getimagesize($filepath);
        if (!$sizeArray && substr_count($filepath, 'https://')) {
            $optionArray = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ),
            );
            $context  = stream_context_create($optionArray);
            $data = file_get_contents($filepath, false, $context);
            $sizeArray = getimagesizefromstring($data);
        }
        if (!$sizeArray) {
            // картинка недоступна
            return false;
        }

        if (in_array($sizeArray['mime'], $allowMIME)) {
            return true;
        }

        return false;
    }

    /**
     * Проверка числового значения на корректность
     * Если переданное значение не является числовым - возвращает false
     *
     * @param float $number
     * @param bool $correct если true, то возвращает числовое значение в
     * отформатированном виде иначе не меняет первое после проверки
     *
     * @author Ramm
     *
     * @return mixed
     */
    public static function CheckNumberFormat($number, $correct = true) {
        $number = str_replace(',', '.', $number);
        if (!is_numeric($number)) {
            return false;
        } elseif ($correct) {
            return number_format($number, 2, '.', '');
        } else {
            return $number;
        }
        return false;
    }

    /**
     * Проверить, является ли строка в UTF-8
     *
     * @param string $string
     *
     * @return bool
     */
    public static function StringInUTF8($string) {
        if (preg_match('//u', $string)) {
            return true;
        }
        return false;
    }

    /**
     * Проверить файл на допустимость формата.
     * Нужно указывать тип, полученный POST-ом
     *
     * @param string $type
     * @param array $allowFormats
     *
     * @return bool
     */
    public static function CheckFileFormat($type, $allowMIME = array(
        'application/vnd.ms-excel', 'application/pdf', 'application/zip', 'application/msword')) {
        if (in_array($type, $allowMIME)) {
            return true;
        }

        // @todo wtf
        return false;
    }

    /**
     * Проверить слово на корректность ввода.
     * Допустимы:
     * слово
     * слово.
     *
     * @param string $word
     *
     * @return bool
     */
    public static function CheckWord($word) {
        if (preg_match("/([0-9_])+/ius", $word)) {
            // если есть цифры или _
            return false;
        }
        if (preg_match("/^(\w+[-|,|`]*\s*\.?\s*)+$/ius", $word)) {
            return true;
        } else {
            if (preg_match("/\((.*)\)/ius", $word, $r)) {
                return Checker::CheckWord($r[1]);
            }
        }
        return false;
    }

}