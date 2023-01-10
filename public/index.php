<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/config/DB.php';
require __DIR__ . '/../src/config/Logger.php';


require __DIR__ . '/../src/components/rateApi/BaseAdaptor.php';
require __DIR__ . '/../src/components/rateApi/BlockchainAdaptor.php';

require __DIR__ . '/../src/components/telegram/Adaptor.php';
require __DIR__ . '/../src/components/telegram/Handler.php';
require __DIR__ . '/../src/components/telegram/Response.php';

require __DIR__ . '/../src/components/schedule/Handler.php';



// Instantiate App
use Slim\Factory\AppFactory;

$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Register routes
require __DIR__ . '/../src/routes.php';

$app->run();