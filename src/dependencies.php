<?php
// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

// custom dependancy injection for postgres
$container['mypostgres'] = function ($c) {
    $mypostgres = new App\api\v1\MyPostgres($c);
    return $mypostgres;
};

//custom dependancy injection for shared memory
$container['myshmop'] = function ($c) {
    $shmop = new App\api\v1\MyShmop($c);
    return $shmop;
};

// custom dependancy injection for mail
$container['mymail'] = function ($c) {
    $mail = new App\api\v1\MyMail($c);
    return $mail;
};

