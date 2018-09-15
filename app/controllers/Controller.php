<?php

namespace  VP2\app\Controllers;

    use  VP2\app\Views\View;
    use  VP2\app\Models\User;
    use  VP2\app\Core\Config;

    abstract class Controller
    {
        protected $model;
        protected $view;

        public function __construct()
        {
            $this->view = new View();
        }

        public static function getUserInfoByCookie()
        {
            $userInfo = [];
            if (!isset($_COOKIE['user_id'])) { // Это неавторизованный пользователь
                $userInfo['authorized'] = false;
                return $userInfo;
            }
            // Это авторизованный пользователь
            $userInfo['authorized'] = true;

            // Расшифровываем id пользователя из куки
            $cryptedUserId = $_COOKIE['user_id'];
            $userInfo['id'] = User::decryptUserId($cryptedUserId, Config::getCookieCryptPassword());

            // Берём доп. информацию о пользователе у модели
            $usrInf = User::getUserInfoById($userInfo['id']);

            if (empty($usrInf)) {
                // Упс... А пользователя такого нету...
                $userInfo = [];
                $userInfo['authorized'] = false;
                return $userInfo;
            }
            return array_merge($userInfo, $usrInf);
        }
}
