<?php

namespace src\components\schedule;

use src\config\DB;

class Handler
{
    const TYPE_DISABLED   = 0;
    const TYPE_EVERY_HOUR = 1;
    const TYPE_EVERY_DAY  = 2;

    public function getUsersToNotify(): array
    {
        $users = DB::query(" select user_id from user_schedule where type = " . self::TYPE_EVERY_DAY .
                               " or (type = " . self::TYPE_EVERY_DAY . " and hour = " . date('H:i') . ")");

        return array_values($users);
    }
}