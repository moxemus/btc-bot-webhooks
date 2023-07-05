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
    if (!in_array(getenv('API_TOKEN'), $request->getHeader('X-Telegram-Bot-Api-Secret-Token'))) {
        return $httpResponse->withStatus(401);
    }

    $json = $request->getBody();
    $responseDate = json_decode($json, true);

    $response = new TelegramResponse($responseDate);
    $user = DB::queryOne("select * from users where telegram_id = $response->id");

    # Ignore banned users
    if (!$user->active || !$response->isValid) {
        return $httpResponse;
    }

    $handler = new TelegramHandler();

    if (!$response->isCommand) {
        switch (true) {
            case str_starts_with($response->text, 'alarm'):
                $handler->setUserAlarm($user->telegram_id, $response->text);
                break;
            case $user->is_admin:
                $handler->sendAdminMenu($user->telegram_id);
                break;
            default:
                $handler->sendCurrentRate($user->telegram_id);
                break;
        }

        return $httpResponse;
    }

    if ($response->text == TelegramResponse::COMMAND_SHOW_RATE) {
        $handler->sendCurrentRate($user->telegram_id);
        return $httpResponse;
    }

    if ($response->isCallback) {
        switch ($response->text) {
            case TelegramResponse::COMMAND_USERS:
                $user->is_admin == 1 && $handler->sendUsers($response->callbackId);
                break;
            case TelegramResponse::COMMAND_SCHEDULE_EVERY_DAY:
                $handler->sendAnswerCallback($response->callbackId, 'Now you will get crypto rate every day');
                break;
            case TelegramResponse::COMMAND_SCHEDULE_EVERY_HOUR:
                $handler->sendAnswerCallback($response->callbackId, 'Now you will get crypto rate every hour');
                break;
            case TelegramResponse::COMMAND_SCHEDULE_DISABLE:
                $handler->sendAnswerCallback($response->callbackId, 'Schedule disabled');
                break;
            default:
                break;
        }

        return $httpResponse;
    }

    switch ($response->text) {
        case TelegramResponse::COMMAND_START:
            $handler->sendWelcome($response);
            break;
        case TelegramResponse::COMMAND_SCHEDULE:
            $handler->sendScheduleMenu($user->telegram_id);
            break;
        case TelegramResponse::COMMAND_CREATE_ALARM:
            $handler->sendAlarmInfo($user->telegram_id);
            break;
        default:
            break;
    }


    return $httpResponse;
});
