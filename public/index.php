<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/handlers/Telegram.php';
require __DIR__ . '/../src/config/DB.php';
require __DIR__ . '/../src/adaptors/BaseRateAPI.php';
require __DIR__ . '/../src/adaptors/BlockchainAPI.php';
require __DIR__ . '/../src/adaptors/Telegram.php';

// Instantiate App
use Slim\Factory\AppFactory;

$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Register routes
require __DIR__ . '/../src/routes.php';

$app->run();