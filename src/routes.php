<?php

namespace src;

use MiladRahimi\PhpConfig\Config;
use MiladRahimi\PhpConfig\Repositories\FileRepository;
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
$app->options('/{routes:.+}', function (Request $request, Response $response)
{
    return $response
        ->withHeader('Access-Control-Allow-Origin', 'https://btc-bot.herokuapp.com')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

/**
 * Test request
 *
 */
$app->get('/', function (Request $request, $response)
{
    $response->getBody()->write("Hello I'm alive!");
    return $response;
});

/**
 * Request for mass-mailing
 * Scheduler calls it every hour
 *
 */
$app->get('/mail', function (Request $request, Response $response)
{
    $config = new Config(new FileRepository(__DIR__ . '/../config.php'));
    if ($request->getHeader('secret_token') != $config->get('api.token'))
    {
        return $response->withStatus(401);
    }

    $handler = new TelegramHandler();
    $handler->mail();
});

/**
 * Webhook request for telegram API
 * telegram calls it every time when we have new user message
 *
 */
$app->post('/webhook', function (Request $request, Response $response)
{
    $config = new Config(new FileRepository(__DIR__ . '/../config.php'));
    if ($request->getHeader('secret_token') != $config->get('api.token'))
    {
        return $response->withStatus(401);
    }

    $json = $request->getBody();
    $responseDate = json_decode($json, true);

    Logger::logToDB($json, Logger::TELEGRAM_WEBHOOK_REQUEST);

    $telegramResponse  = new TelegramResponse($responseDate);
    $handler           = new TelegramHandler();

    # Handling
    if ($telegramResponse->isValid)
    {
        $user = DB::queryOne("select * from users where id = $telegramResponse->id");

        if ($telegramResponse->isCommand)
        {
            # Show rate
            if ($telegramResponse->text == TelegramResponse::COMMAND_SHOW_RATE)
            {
                $handler->sendCurrentRate($user->id);
                return $response;
            }


            if ($telegramResponse->isCallback)
            {
                $callbackId = $responseDate['callback_query']['id'] ?? null;

                if (is_null($callbackId)) return $response;

                # Sending users count to Admin
                if ($telegramResponse->text == TelegramResponse::COMMAND_USERS && $user->is_admin == 1)
                {
                    $handler->sendUsers($callbackId);
                }

                # Setting up schedule answer from User
                if ($telegramResponse->text == TelegramResponse::COMMAND_SCHEDULE_EVERY_DAY)
                {
                    $handler->sendAnswerCallback($callbackId, 'Now you will get BTC rate every day');
                }

                if ($telegramResponse->text == TelegramResponse::COMMAND_SCHEDULE_EVERY_HOUR)
                {
                    $handler->sendAnswerCallback($callbackId, 'Now you will get BTC rate every hour');
                }

                if ($telegramResponse->text == TelegramResponse::COMMAND_SCHEDULE_DISABLE)
                {
                    $handler->sendAnswerCallback($callbackId, 'Schedule disabled');
                }
            }
            else
            {
                # Start command
                if ($telegramResponse->text == TelegramResponse::COMMAND_START)
                {
                    $handler->sendWelcome($user->id);
                }

                # Setting up schedule
                if ($telegramResponse->text == TelegramResponse::COMMAND_SCHEDULE)
                {
                    $handler->sendScheduleMenu($user->id);
                }


            }
        }
        else
        {
            if ($user->is_admin == 1)
            {
                $handler->sendAdminMenu($user->id);
            }
            else
            {
                $handler->sendCurrentRate($user->id);
            }
        }
    }

    return $response;
});
