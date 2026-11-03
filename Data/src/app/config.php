<?php
$container->set('config_bd',function(){
    return(object)[
        'host' => $_ENV['DB_HOST'],
        'username' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASSW'],
        'database' => $_ENV['DB_NAME'],
        'charset' => 'utf8mb4'
    ];
});