<?php

namespace src\components\telegram;

use src\components\rateApi\BaseAdaptor as RateAdaptor;
use src\components\rateApi\MessariAdaptor;
use moxemus\array\Helper as ArrayHelper;
use src\components\rateApi\BaseAdaptor;
use src\components\telegram\Response as TelegramResponse;
use src\config\DB;

class Handler
{
    const SMILE_GREEN = "\xE2\x9C\x85";
    const SMILE_RED = "\xF0\x9F\x94\xBB";
    const SMILE_EXCLAMATION = "\xE2\x9D\x97";

    const PURPLE_HEART = "\xF0\x9F\x92\x9C";
    const SMILE_DOG = "\xF0\x9F\x90\xB6";
    const SMILE_DIAMOND = "\xF0\x9F\x92\xA0";
    const SMILE_LETTER_B = "\xF0\x9F\x85\xB1";
    const SMILE_DVD = "\xF0\x9F\x93\x80";

    protected Adaptor $telegramAdaptor;
    protected BaseAdaptor $apiAdaptor;
    protected DB $db;

    public $user;

    public TelegramResponse $response;
    protected $chatId;

    /**
     * @param Response $response
     * @param $user
     * @param RateAdaptor|null $apiAdaptor
     */
    public function __construct(TelegramResponse $response, $user, ?BaseAdaptor $apiAdaptor = null)
    {
        $this->apiAdaptor = $apiAdaptor ?? new MessariAdaptor();
        $this->telegramAdaptor = new Adaptor();
        $this->db = new DB();

        $this->response = $response;
        $this->chatId = $response->id;
        $this->user = $user;
    }

    /**
     * @return bool
     */
    public function processAnswer(): bool
    {
        return match (true) {
            !$this->user => $this->sendWelcome(),
            !$this->response->isCommand => $this->processAnswerNoCommand(),
            $this->response->isCallback => $this->processCallBack(),
            default => $this->processAnswerCommand()
        };
    }

    public function processCallBack(): bool
    {
        return match ($this->response->text) {
            TelegramResponse::COMMAND_USERS => $this->sendUsersCallback(),
            TelegramResponse::COMMAND_SCHEDULE_EVERY_DAY => $this->sendAnswerCallback('Now you will get crypto rate every day'),
            TelegramResponse::COMMAND_SCHEDULE_EVERY_HOUR => $this->sendAnswerCallback('Now you will get crypto rate every hour'),
            TelegramResponse::COMMAND_SCHEDULE_DISABLE => $this->sendAnswerCallback('Schedule disabled'),
            default => false
        };
    }

    public function processAnswerCommand(): bool
    {
        return match ($this->response->text) {
            TelegramResponse::COMMAND_START => $this->sendWelcome(),
            TelegramResponse::COMMAND_SCHEDULE => $this->sendScheduleMenu(),
            TelegramResponse::COMMAND_CREATE_ALARM => $this->sendAlarmInfo(),
            TelegramResponse::COMMAND_SHOW_RATE => $this->sendCurrentRate(),
            default => false
        };
    }

    public function processAnswerNoCommand(): bool
    {
        return match (true) {
            str_starts_with($this->response->text, 'alarm') => $this->setUserAlarm($this->response->text),
            $this->user->is_admin => $this->sendAdminMenu(),
            default => $this->sendCurrentRate()
        };
    }

    /**
     * @return string[]
     */
    protected function getAvailableCrypto(): array
    {
        return [
            RateAdaptor::BTC,
            RateAdaptor::BCH,
            RateAdaptor::DOGE,
            RateAdaptor::MATIC
        ];
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getCurrencyName(string $name): string
    {
        return match ($name) {
            RateAdaptor::BTC => self::SMILE_LETTER_B,
            RateAdaptor::ETH => self::SMILE_DIAMOND,
            RateAdaptor::DOGE => self::SMILE_DOG,
            RateAdaptor::MATIC => self::PURPLE_HEART,
            RateAdaptor::BCH => self::SMILE_DVD
        };
    }

    /**
     * @return void
     */
    public function mail(): void
    {
        $users = DB::query("SELECT telegram_id, is_admin, last_rate from users");

        $data = array_map(
            fn($currency) => [
                'currency' => $currency,
                'value' => $this->apiAdaptor->getRate($currency)
            ],
            $this->getAvailableCrypto()
        );

        foreach ($users as $user) {
            $message = '';
            $chatId = $user['telegram_id'];

            foreach ($data as $item) {
                $currentRate = $item['value'];
                $currency = $item['currency'];
                $lastRate = $this->getLastUserRate($currency);

                $message .= $this->getCurrencyName($currency) .
                    ': ' . $this->getRateMessage($currentRate, $lastRate) .
                    PHP_EOL;

                $this->updateUserRate($chatId, $currentRate, $currency);
            }

            $this->telegramAdaptor->sendMessage($chatId, $message);
        }
    }

    /**
     * @return void
     */
    public function notify(): void
    {
        $userAlarms = DB::query("select user_id, rate, is_bigger, currency from user_alarms where active <> 0");

        foreach ($userAlarms as $alarm) {
            $userRate = $alarm['rate'];
            $isBigger = (bool)$alarm['is_bigger'];
            $chatId = $alarm['user_id'];
            $currency = $alarm['currency'];
            $currentRate = $this->apiAdaptor->getRate($currency);

            if (
                ($userRate < $currentRate && $isBigger) ||
                ($userRate > $currentRate && !$isBigger)
            ) {
                $text = ($isBigger) ? 'more' : 'less';
                $message =
                    self::SMILE_EXCLAMATION .
                    "{$currency} costs is {$text} than {$userRate} now - {$currentRate}" .
                    self::SMILE_EXCLAMATION;

                $this->telegramAdaptor->sendMessage($chatId, $message);

                DB::exec("update user_alarms set active = 0, sent = now() where user_id = " . $chatId);

                $this->updateUserRate($chatId, $currentRate, $currency);
            }
        }
    }

    /**
     * @param string $text
     *
     * @return bool
     */
    public function setUserAlarm(string $text): bool
    {
        $matches = [];
        preg_match('/alarm (\w+) (\w+) ([-+]?[0-9]*\.?[0-9]*)/', $text, $matches);

        $userId = $this->user->id;
        $currency = $matches[1] ?? null;
        $sign = $matches[2] ?? null;
        $rate = $matches[3] ?? 0;

        if (!in_array($currency, self::getAvailableCrypto())) {
            $this->sendMessage($userId, 'Please select correct currency');
        } elseif (!in_array($sign, ['more', 'less']) || $rate <= 0) {
            $this->sendMessage($userId, 'Please give correct info');
        } else {
            $isBigger = intval($sign == 'more');

            DB::exec("delete from user_alarms where user_id = $userId");
            DB::exec(
                "insert into user_alarms (user_id, rate, is_bigger, currency, active) " .
                "values ($userId, $rate, $isBigger, $currency, 1)"
            );

            $this->sendMessage($userId, 'New alarm configured');
        }

        return true;
    }

    /**
     *
     * @return bool
     */
    public function sendCurrentRate(): bool
    {
        $data = array_map(
            fn($currency) => [
                'currency' => $currency,
                'value' => $this->apiAdaptor->getRate($currency)
            ],
            $this->getAvailableCrypto()
        );

        $message = '';
        foreach ($data as $item) {
            $currentRate = $item['value'];
            $currency = $item['currency'];
            $lastRate = $this->getLastUserRate($currency);

            $message .= $this->getCurrencyName($currency) .
                ': ' . $this->getRateMessage($currentRate, $lastRate) .
                PHP_EOL;

            $this->updateUserRate($this->chatId, $currentRate, $currency);
        }

        return $this->telegramAdaptor->sendMessage($this->chatId, $message);
    }

    /**
     * @return bool
     */
    public function sendAdminMenu(): bool
    {
        $markupParams = [
            "Show users" => Response::COMMAND_USERS,
            "Show rate" => Response::COMMAND_SHOW_RATE
        ];

        return $this->sendMessage('Hello admin!', $markupParams);
    }

    /**
     * @return bool
     */
    public function sendUsersCallback(): bool
    {
        if ($this->user->is_admin == 1 || !$this->user) {
            return false;
        }
        $raw = DB::queryOne("select count(*) as cc from users");

        return $this->sendAnswerCallback( $raw->cc);
    }

    /**
     * @return bool
     */
    public function sendScheduleMenu(): bool
    {
        $markupParams = [
            "Every day" => Response::COMMAND_SCHEDULE_EVERY_DAY,
            "Every hour" => Response::COMMAND_SCHEDULE_EVERY_HOUR,
            "Disable notifications" => Response::COMMAND_SCHEDULE_DISABLE
        ];

        return $this->sendMessage('Set up your notification schedule', $markupParams);
    }

    /**
     * @return bool
     */
    public function sendWelcome(): bool
    {
        $this->sendMessage($this->chatId, 'Welcome to BTC rate bot!');
        return $this->sendCurrentRate();
    }

    /**
     * @return void
     */
    public function createUser(): void
    {
        $params = $this->response->userInfo;

        $firstName = $params['first_name'] ?? '';
        $lastName = $params['last_name'] ?? '';
        $language = $params['language_code'] ?? '';
        $username = $params['username'] ?? '';

        DB::exec(
            "insert into users (telegram_id, first_name, last_name, username, language_code) values " .
            "($this->chatId, '$firstName', '$lastName', '$username', '$language')"
        );

        $currencies = $this->getAvailableCrypto();
        foreach ($currencies as $currency) {
            DB::exec(
                "insert into user_rates (user_id, currency, value) values " .
                "($this->chatId, '$currency', 0)"
            );
        }
    }

    /**
     * @param string $currency
     *
     * @return float
     */
    protected function getLastUserRate(string $currency): float
    {
        $raw = DB::queryOne("select value from user_rates where user_id = " . $this->chatId . " and currency = '$currency'");

        return (float)($raw->value ?? 0);
    }

    /**
     * @return bool
     */
    public function sendAlarmInfo(): bool
    {
        return $this->sendMessage(
            "You can get notification when selected currency rate will be more/lower than your price.\n" .
            "Write your alarm template, for example: alarm btc less 22000 \n"
        );
    }

    /**
     * @param string $text
     *
     * @return bool
     */
    public function sendAnswerCallback(string $text): bool
    {
        $callbackId = $this->response->callbackId;
        return $this->telegramAdaptor->sendAnswerCallback($callbackId, $text);
    }

    /**
     * @param string $text
     * @param array $markupParams
     *
     * @return bool
     */
    protected function sendMessage(string $text, array $markupParams = []): bool
    {
        return $this->telegramAdaptor->sendMessage($this->chatId, $text, $markupParams);
    }

    /**
     * @param float $currentRate
     * @param float $lastRate
     *
     * @return string
     */
    protected function getRateMessage(float $currentRate, float $lastRate): string
    {
        $smile = ($currentRate >= $lastRate) ? self::SMILE_GREEN : self::SMILE_RED;
        return $currentRate . $smile;
    }

    /**
     * @param int $chatId
     * @param float $rate
     * @param string $currency
     *
     * @return void
     */
    protected function updateUserRate(int $chatId, float $rate, string $currency): void
    {
        DB::exec("UPDATE user_rates set value = " . number_format($rate, 3, '.', '')
            . "  where user_id = {$this->chatId} and currency = '{$currency}'");
    }
}
