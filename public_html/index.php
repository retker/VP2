<?php

use  VP2\app\Core\Config;
use  VP2\app\Core\Db;
use  VP2\app\Core\Router;

define('ROOT', realpath(__DIR__ . '/..'));
define('APP', ROOT . '/app');

ini_set('display_errors', 1);
error_reporting(E_ALL);

mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");

require_once ROOT . '/vendor/autoload.php';

// Загружаем конфигурацию
Config::loadConfig();

// Установка соединения с БД
Db::setConnection();

// Запускаем Router
Router::run();
