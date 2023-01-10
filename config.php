<?php

return [
    'mysql' => [
        'host'     => getenv('MYSQL_HOST'),
        'user'     => getenv('MYSQL_USER'),
        'password' => getenv('MYSQL_PASSWORD'),
        'dbname'   => getenv('MYSQL_DATABASE')
    ],
    'telegram' => [
        'url'   => 'https://api.telegram.org/bot',
        'token' => getenv('TELEGRAM_TOKEN')
    ],
    'api' => [
        'token' => getenv('API_TOKEN'),
    ]
];