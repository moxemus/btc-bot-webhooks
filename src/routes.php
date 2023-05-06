<?php

namespace src;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use src\components\telegram\Handler as TelegramHandler;
use src\components\telegram\Response as TelegramResponse;
use src\config\DB;
use src\config\Logger;

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
    $handler = new TelegramHandler();

    # Ignore banned users
    $user = DB::queryOne("select active from users where telegram_id = " . $response->id);
    if ($user->active === 0) {
        return $httpResponse;
    }

    Logger::logToDB($json, Logger::TELEGRAM_WEBHOOK_REQUEST);
    Logger::logTelegramResponse($response);

    # Handling
    if ($response->isValid) {
        $user = DB::queryOne("select * from users where telegram_id = $response->id");

        # If response - command
        if ($response->isCommand) {
            # Show rate
            if ($response->text == TelegramResponse::COMMAND_SHOW_RATE) {
                $handler->sendCurrentRate($user->telegram_id);
                return $httpResponse;
            }

            # If response in answer for callback
            if ($response->isCallback) {
                $callbackId = $responseDate['callback_query']['id'] ?? null;

                if (is_null($callbackId)) return $httpResponse;

                # Sending users count to Admin
                if ($response->text == TelegramResponse::COMMAND_USERS && $user->is_admin == 1) {
                    $handler->sendUsers($callbackId);
                } # Setting up schedule answer from User
                else if ($response->text == TelegramResponse::COMMAND_SCHEDULE_EVERY_DAY) {
                    $handler->sendAnswerCallback($callbackId, 'Now you will get BTC rate every day');
                } else if ($response->text == TelegramResponse::COMMAND_SCHEDULE_EVERY_HOUR) {
                    $handler->sendAnswerCallback($callbackId, 'Now you will get BTC rate every hour');
                } else if ($response->text == TelegramResponse::COMMAND_SCHEDULE_DISABLE) {
                    $handler->sendAnswerCallback($callbackId, 'Schedule disabled');
                }
            } else {
                # Start command
                if ($response->text == TelegramResponse::COMMAND_START) {
                    $handler->sendWelcome($response);
                } # Setting up schedule
                else if ($response->text == TelegramResponse::COMMAND_SCHEDULE) {
                    $handler->sendScheduleMenu($user->telegram_id);
                } # Setting up alarms
                else if ($response->text == TelegramResponse::COMMAND_CREATE_ALARM) {
                    $handler->sendAlarmInfo($user->telegram_id);
                }
            }
        } else {
            # If User send just a message without any command
            if (str_starts_with($response->text, 'alarm')) {
                $handler->setUserAlarm($user->telegram_id, $response->text);
            } elseif ($user->is_admin == 1) {
                $handler->sendAdminMenu($user->telegram_id);
            } else {
                $handler->sendCurrentRate($user->telegram_id);
            }
        }
    }

    return $httpResponse;
});
