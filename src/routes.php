<?php

namespace src;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use src\handlers\Telegram;

$app = AppFactory::create();

$app->options('/{routes:.+}', function (Request $request, Response $response)
{
    return $response;
});

/**
 * Test request
 *
 */
$app->get('/', function (Request $request, $response)
{
    $response->getBody()->write('Hello Iam alive!');
    return $response;
});

/**
 * Request for mass-mailing
 * Scheduler calls it every hour
 *
 */
$app->post('/mail', function (Request $request, Response $response)
{
    $handler = new Telegram();
    $handler->mail();
});

/**
 * Webhook request for Telegram API
 * Telegram calls it every time when we have new user message
 *
 */
$app->post('/webhook', function (Request $request, Response $response)
{
    $json = $request->getBody();
    $data = json_decode($json, true);

    $chatId = $data['message']['from']['id'] ?? null;
    if ($chatId)
    {
        $handler = new Telegram();

        if ($handler->sendCurrentRate($chatId))
        {
            $response->getBody()->write('success' . $chatId);
        }
    }

    return $response;
});
