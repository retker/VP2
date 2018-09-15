<?php

return [
    'db' => [
        'host' => 'localhost',
        'dbname' => 'Loftschool_VP2',
        'username' => 'root',
        'password' => '123',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci'
    ],
    'cookie' => [
        'cryptPassword' => 'IuJkLr34Dfb0196',
        'liveTime' => 1960
    ],
    'photosFolder' => APP . '/_photos_',
    'user' => [
        'minLoginLength' => 4,
        'maxLoginLength' => 15,
        'minPasswordLength' => 5,
        'maxPasswordLength' => 20
    ]
];
