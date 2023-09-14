<?php

namespace src;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use src\components\telegram\Handler as TelegramHandler;
use src\components\telegram\Response as TelegramResponse;
use src\config\DB;

$app = AppFactory::create();

/**
 * Options request for all routes
 *
 */
$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', 'https://btc-bot.herokuapp.com')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

/**
 * Test request
 *
 */
$app->get('/', function (Request $request, $response) {
    $response->getBody()->write("Hello I'm alive!");
    return $response;
});

/**
 * Scheduler request for mass-mailing
 * Scheduler calls it every hour
 *
 */
$app->get('/mail', function (Request $request, Response $response) {
    if (!in_array(getenv('API_TOKEN'), $request->getHeader('Authorization'))) {
        return $response->withStatus(401);
    }

    $handler = new TelegramHandler();
    $handler->mail();

    return $response;
});

/**
 * Scheduler request for notify users about their rate alarms
 * Scheduler calls it every minute
 *
 */
$app->get('/notify', function (Request $request, Response $response) {
    if (!in_array(getenv('API_TOKEN'), $request->getHeader('Authorization'))) {
        return $response->withStatus(401);
    }

    $handler = new TelegramHandler();
    $handler->notify();

    return $response;
});

/**
 * Webhook request for telegram API
 * Telegram calls it every time when we have new user message
 *
 */
$app->post('/webhook', function (Request $request, Response $httpResponse) {
    $apiToken = getenv('API_TOKEN');
    $clientApiToken = $request->getHeader('X-Telegram-Bot-Api-Secret-Token');
    if (!in_array($apiToken, $clientApiToken)) {
        return $httpResponse->withStatus(401);
    }

    $jsonData = $request->getBody();
    $responseData = json_decode($jsonData, true);

    $response = new TelegramResponse($responseData);
    $handler = new TelegramHandler();

    # Process answering
    if ($response->isValid) {
        $handler->processAnswer($response);
    }

    return $httpResponse;
});
