<?php

require '../../vendor/autoload.php';
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use VP2\app\Models\User;
require ('../core/Db.php');
//$cfg = require('config.php');
//$db = $cfg['db'];



//$capsule = new Capsule;
//$capsule->addConnection([
//    'driver' => 'mysql',
//    'host' => $db['host'],
//    'database' => $db['dbname'],
//    'username' => $db['username'],
//    'password' => $db['password'],
//    'charset' => $db['charset'],
//    'collation' => $db['collation'],
//    'prefix' => '',
//]);
//
//$capsule->setAsGlobal();
//$capsule->bootEloquent();

$user = User::all();

print_r($user);




