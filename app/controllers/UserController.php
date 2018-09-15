<?php

namespace  VP2\app\Controllers;

use  VP2\app\Models\User;
use  VP2\app\Core\Config;
use Intervention\Image\ImageManagerStatic as Image;


class UserController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new User();
        Image::configure(array('driver' => 'gd'));
    }

    public function actionRegister(array $params)
    {
        $viewData = [];
        $viewData['curSection'] = 'register';

        if (count($params) == 0) {
            // Нет параметров - показываем пустую форму регистрации
            $this->view->render('register', $viewData);
        } else {
            $userData = [];
            foreach (User::$userDataFields as $field) {
                $userData[$field] = isset($params[$field]) ? $params[$field] : '';
            }
            $userData['login'] = strtolower(trim($userData['login']));
            $userData['password'] = trim($userData['password']);
            $userData['password-again'] = trim($userData['password-again']);

            // Проверяем параметры на корректность
            $checkParamsResult = $this->checkRegisterParams($userData['login'], $userData['password'], $userData['password-again']);

            if ($checkParamsResult === true) {
                // Входные параметры - OK.  Обращаемся к модели пользователя - регистрируем его
                if ($this->model->isLoginExists($userData['login'])) {
                    // Пользователь с таким логином уже есть
                    header('refresh: 2; url=/register');
                    $viewData['errorMessage'] = 'Пользователь с таким логином уже есть';
                    $this->view->render('error', $viewData);
                } else {
                    $userId = $this->model->CreateNewUser($userData);
                    // Если есть фотка, то помещаем её в папку для фоток с именем "mainphoto_".$userId."jpg"
                    if (isset($_FILES['photo'])) {
                        $this->saveUserPhoto($userId, $_FILES['photo']['tmp_name']);
                    }
                    setcookie('user_id', User::encryptUserId($userId, Config::getCookieCryptPassword()), time() + Config::getCookieLiveTime(), '/', $_SERVER['SERVER_NAME']);
                    // После сообщения об успешной регистрации - автоматически перейдём в админ панель через 2 секунды
                    header('refresh: 2; url=/admin');
                    $viewData['successMessage'] = "Поздравляем! Регистрация прошла успешно!<br>Ваш логин:&nbsp; <b>{$userData['login']}</b>";
                    $this->view->render('success', $viewData);
                }
            } else {
                // Некорректные входные параметры
                header('refresh: 2; url=/register');
                $viewData['errorMessage'] = $checkParamsResult;
                $this->view->render('error', $viewData);
            }
        }
    }

    public function actionAuth(array $params)
    {
        $viewData = [];
        $viewData['curSection'] = 'auth';

        if (count($params) == 0) {
            // Нет параметров - показываем пустую форму авторизации
            $this->view->render('auth', $viewData);
        } else {
            $userData['login'] = isset($params['login']) ? strtolower(trim($params['login'])) : '';
            $userData['password'] = isset($params['password']) ? trim($params['password']) : '';

            if ($this->model->isLoginExists($userData['login'])) {
                // Логин найден - проверяем пароль
                $pwhash = $this->model->getPasswordHash($userData['login']);
                if (password_verify($userData['password'], $pwhash)) {
                    // Успешная авторизация
                    $userId = $this->model->getUserId($userData['login']);
                    setcookie('user_id', User::encryptUserId($userId, Config::getCookieCryptPassword()), time() + Config::getCookieLiveTime(), '/', $_SERVER['SERVER_NAME']);
                    // После приветствия - автоматически перейдём в админ панель через 2 секунды
                    header('refresh: 2; url=/admin');
                    $userInfo = User::getUserInfoById($userId);
                    $viewData['successMessage'] = "Привет,&nbsp; <b>{$userInfo['name']}</b> !";
                    $this->view->render('success', $viewData);
                } else {
                    $viewData['errorMessage'] = 'Неверный пароль';
                    $this->view->render('error', $viewData);
                }
            } else {
                $viewData['errorMessage'] = 'Пользователь с таким логином не найден';
                $this->view->render('error', $viewData);
            }
        }
    }

    public function actionLogout()
    {
        setcookie('user_id', '', time() - 5, '/', $_SERVER['SERVER_NAME']);
        header('Location: /');
    }


    public function actionPhoto(array $params)
    {
        if (empty($params['request_from_url'])) {
            return;
        }
        $userInfo = self::getUserInfoByCookie();
        if (!$userInfo['authorized']) {
            // Не авторизованному - не отдаём
            header('HTTP/1.0 403 Forbidden');
            echo 'You are not authorised user!';
            return;
        }
        // Нужно отдать картинку
        $photoFilename = Config::getPhotosFolder() . '/photo_' . $params['request_from_url'] . '.jpg';
        if (!file_exists($photoFilename)) {
            // отдаём пустую картинку 1x1 пиксель
            $photoFilename = Config::getPhotosFolder() . '/empty.jpg';
        }

        header("Content-Type: image/jpeg");
        header("Content-Length: " . filesize($photoFilename));
        echo file_get_contents($photoFilename);
    }


    public function actionMainPhoto($id)
    {
        $userInfo = self::getUserInfoByCookie();
        if (!$userInfo['authorized']) { // Не авторизованному - не отдаём
            header('HTTP/1.0 403 Forbidden');
            echo 'You are not authorized user!';
            return;
        }

        (empty($id)) ? ($userId = $userInfo['id']) : ($userId = $id['request_from_url']);

        $photoFilename = Config::getPhotosFolder() . '/mainphoto_' . $userId . '.jpg';
        if (!file_exists($photoFilename)) {
            $photoFilename = Config::getPhotosFolder() . '/empty_user.jpg';
        }
        header("Content-Type: image/jpeg");
        header("Content-Length: " . filesize($photoFilename));
        echo file_get_contents($photoFilename);
    }


    private function checkRegisterParams($login, $password, $passwordAgain)
    {
        if (!is_string($login) || !is_string($password) || !is_string($passwordAgain)) {
            return 'Параметры должны быть строковыми';
        }
        if ((strlen($login) < Config::getMinLoginLength()) || (strlen($login) > Config::getMaxLoginLength())) {
            return 'Логин должен содержать от ' . Config::getMinLoginLength() .
                ' до ' . Config::getMaxLoginLength() . ' символов';
        }
        if ((strlen($password) < Config::getMinPasswordLength()) || (strlen($password) > Config::getMaxPasswordLength())) {
            return 'Пароль должен содержать от ' . Config::getMinPasswordLength() .
                ' до ' . Config::getMaxPasswordLength() . ' символов';
        }
        if (!preg_match('/^[a-z0-9_-]{1,}$/', $login)) {
            return 'Логин должен состоять из строчных латинских букв, цифр, символов подчеркивания и дефиса';
        }
        if ($password != $passwordAgain) {
            return 'Пароли не совпадают';
        }
        return true;
    }

    private function saveUserPhoto($userId, $tmpFileName)
    {
        if (empty($tmpFileName)) {
            return false;
        }

        $img = Image::make($tmpFileName);
        // Вырезаем область в пропорции 3x4
        $img->crop(round(0.75 * $img->height()), $img->height());
        $img->crop($img->width(), round(1.33333 * $img->width()));
        if ($img->width() > 300) {
            $img->resize(300, null, function ($constraint) {
                $constraint->aspectRatio();
            });
        }
        // Сохраняем в папку с фотками пользователей
        if (!file_exists(Config::getPhotosFolder())) {
            mkdir(Config::getPhotosFolder(), 0777);
        }
        if (!file_exists(Config::getPhotosFolder() . '/thumbs')) {
            mkdir(Config::getPhotosFolder() . '/thumbs', 0777);
        }
        $img->save(Config::getPhotosFolder() . '/mainphoto_' . $userId . '.jpg', 90);
        // Удаляем временный файл
        unlink($tmpFileName);
        return true;
    }
}
