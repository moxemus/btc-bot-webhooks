<?php

namespace src\components\telegram;

final class Response
{
    const COMMAND_START = '/start';
    const COMMAND_HELP = '/help';
    const COMMAND_SCHEDULE = '/schedule';
    const COMMAND_SCHEDULE_EVERY_DAY = '/schedule_every_day';
    const COMMAND_SCHEDULE_EVERY_HOUR = '/schedule_every_hour';
    const COMMAND_SCHEDULE_DISABLE = '/schedule_disable';
    const COMMAND_USERS = '/users';
    const COMMAND_SHOW_RATE = '/show_rate';
    const COMMAND_CREATE_ALARM = '/create_alarm';

    private array $data;
    public int $id;
    public string $text;
    public bool $isCommand = false;
    public bool $isCallback = false;
    public bool $isValid = false;
    public array $userInfo = [];

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;

        try {
            $this->isCallback = array_key_exists('callback_query', $this->data);
            if ($this->isCallback) {
                $this->id = (int)$this->data['callback_query']['message']['chat']['id'];
                $this->text = $this->data['callback_query']['data'];
                $this->isCommand = true;
            } else {
                $this->id = (int)$this->data['message']['from']['id'];
                $this->text = $this->data['message']['text'] ?? '';
                $this->isCommand = str_starts_with($this->text, '/');
            }

            $this->userInfo = $this->data['message']['from'] ?? [];

        } catch (\Exception $e) {

        }

        $this->isValid = (($this->id > 0) && (!empty($this->id)));
    }
}
