<?php

namespace src\config;

use PDO;

final class DB
{
    private static PDO $dbh;

    /**
     * @return void
     */
    private static function connect(): void
    {
        if (empty(self::$dbh)) {
            self::$dbh = new PDO(
                'mysql:host=' . getenv('MYSQL_HOST') .
                ';dbname=' . getenv('MYSQL_DATABASE'),
                getenv('MYSQL_USER'),
                getenv('MYSQL_PASSWORD')
            );
        }
    }

    /**
     * @param string $sql
     *
     * @return array|bool
     */
    public static function query(string $sql): array|bool
    {
        self::connect();

        return self::$dbh->query($sql, PDO::FETCH_ASSOC)->fetchAll();
    }

    /**
     * @param string $sql
     *
     * @return mixed
     */
    public static function queryOne(string $sql): mixed
    {
        self::connect();

        return self::$dbh->query($sql, PDO::FETCH_ASSOC)->fetch(PDO::FETCH_OBJ);
    }

    /**
     * @param string $sql
     *
     * @return void
     */
    public static function exec(string $sql): void
    {
        self::connect();

        self::$dbh->prepare($sql);
        self::$dbh->exec($sql);
    }
}
