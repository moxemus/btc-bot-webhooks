<?php

namespace src\config;

use PDO;

final class DB
{
    private static PDO $dbh;

    private static function connect()
    {
        if (empty(self::$dbh))
        {
            self::$dbh = new PDO('mysql:host=' . getenv('MYSQL_HOST') . ';dbname=' .  getenv('MYSQL_DATABASE'),
                getenv('MYSQL_USER'),  getenv('MYSQL_PASSWORD'));
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
