<?php
/**
 * WebProduction Packages
 * @copyright (C) 2007-2012 WebProduction <webproduction.com.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * В этом файле определяются дополнительные константы, подключаются пакеты, API,
 * подключается автоматат состояний
 * описываются дополнительные вызовы в зависимости от режима работы Engine.
 */

if (PackageLoader::Get()->getMode('development')) {
    // dev-mode

    // выполяем Engine Contents Generator
    Engine::GetGenerator()->process();
}

// include_once(__DIR__.'/api/include.php');

// default (example) auth event observer
class AuthMachine implements Events_IEventObserver {

    public function notify(Events_Event $event) {
        $arguments = Engine::GetURLParser()->getArguments();

        if (isset($arguments['auth_logout'])) {
            MainService::GetFactory()->getAuthService()->logout();
            return false;
        }

        // login
        if (isset($arguments['auth_login']) && isset($arguments['auth_password'])) {
            try {
                $cookie = false;
                if (isset($arguments['auth_cookie'])) {
                    $cookie = true;
                }
                MainService::GetFactory()->getAuthService()->login(
                $arguments['auth_login'],
                $arguments['auth_password'],
                $cookie,
                false
                );
            } catch (ServiceException $e) {
                Engine::Get()->getRequest()->setContentID(401);
                return false;
            }
        } elseif (Engine::Get()->getRequest()->getContentID() == 'logout') {
            // logout
            try {
                MainService::GetFactory()->getAuthService()->logout();
            } catch (Exception $e) {

            }
            return false;
        }

        // проверка прав
        $pageData = Engine::GetContentDataSource()->getDataByID(
        Engine::Get()->getRequest()->getContentID()
        );

        if ($pageData['level']) {
            try {
                $user = MainService::GetFactory()->getAuthService()->getUser();
                if ($user->getLevel() < $pageData['level']) {
                    throw new Engine_Exception('Access denied');
                }
            } catch (Exception $e) {
                Engine::Get()->getRequest()->setContentID(403);
                return false;
            }
        }
    }

}

// Events::Get()->observe('afterQueryDefine', new AuthMachine());
