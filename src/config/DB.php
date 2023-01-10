<?php

namespace src\config;

use MiladRahimi\PhpConfig\Exceptions\InvalidConfigFileException;
use MiladRahimi\PhpConfig\Repositories\FileRepository;
use MiladRahimi\PhpConfig\Config;
use PDO;

final class DB
{
    private static PDO $dbh;

    /**
     * @throws InvalidConfigFileException
     */
    private static function connect()
    {
        if (empty(self::$dbh))
        {
            $config = new Config(new FileRepository(__DIR__ . '/../../config.php'));

            self::$dbh = new PDO('mysql:host=' . $config->get('mysql.host') . ';dbname=' .  $config->get('mysql.dbname'),
                $config->get('mysql.user'),  $config->get('mysql.password'));
        }
    }

    public static function query($sql)
    {
        self::connect();

        return self::$dbh->query($sql, PDO::FETCH_ASSOC)->fetchAll();
    }

    public static function queryOne($sql)
    {
        self::connect();

        return self::$dbh->query($sql, PDO::FETCH_ASSOC)->fetch(PDO::FETCH_OBJ);
    }

    public static function exec($sql)
    {
        self::connect();

        self::$dbh->prepare($sql);
        self::$dbh->exec($sql);
    }
}
