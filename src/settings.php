<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        // Custom settings
        // set values with ansible install(update)_playbook.yml
        // get values with slim php $custom = $this->container->settings['custom'];
        // or like $custom = $this->container->settings['custom']['mysqlDbName'];
        'custom' => [
            'mailUser' => 'secretMailUser',
            'mailPassword' => 'secretMailPassword',
            'mysqlDbName' => 'secretDbName',
            'mysqlDbRootPassword' => 'secretRootPassword',
            'mysqlDbUserName' => 'secretDbUserName',
            'mysqlDbUserPassword' => 'secretDbUserPassword',
            'postgresDbName' => 'secretDbName',
            'postgresDbUserName' => 'secretDbUserName',
            'postgresDbUserPassword' => 'secretDbUserPassword',
        ],
    ],
];

