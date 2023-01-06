<?php

namespace src;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use src\handlers\Telegram;

$app = AppFactory::create();

$app->options('/{routes:.+}', function (Request $request, Response $response, $args)
{
    return $response;
});

//$app->add(function ($req, $res, $next)
//{
//    $response = $next($req, $res);
//
//    return $response
//        ->withHeader('Access-Control-Allow-Origin', '*')
//        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
//        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
//});

$app->get('/', function (Request $request, $response, $args) {

    $response->getBody()->write('Hello Iam alive!');

    return $response;
});

$app->post('/webhook', function (Request $request, Response $response, $args) {

    $json = $request->getBody();
    $data = json_decode($json, true);

    $chatId = $data['message']['from']['id'] ?? null;
    if ($chatId)
    {
        $handler = new Telegram();
        $result = $handler->sendCurrentRate($chatId);

        if ($result === true)
        {
            $response->getBody()->write('success' . $chatId);
        }
    }

    return $response;
});
