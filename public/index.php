<?php

require __DIR__ . '/../vendor/autoload.php';

// Instantiate App
use Slim\Factory\AppFactory;

$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Register routes
require __DIR__ . '/../src/routes.php';

$app->run();