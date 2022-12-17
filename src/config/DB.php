<?php

namespace src\config;

use PDO;

final class DB
{
    static private PDO $dbh;

    static private string $host     = 'localhost';
    static private string $dbname   = 'telegram';
    static private string $user     = 'root';
    static private string $password = 'root';


    static private function connect()
    {
        if (empty(self::$dbh))
        {
            self::$dbh = new PDO('mysql:host=' . DB::$host . ';dbname=' . DB::$dbname, DB::$user, DB::$password);
        }
    }

    static public function query($sql)
    {
        self::connect();

        return self::$dbh->query($sql, PDO::FETCH_ASSOC)->fetchAll();
    }

    static public function queryOne($sql)
    {
        self::connect();

        return self::$dbh->query($sql, PDO::FETCH_ASSOC)->fetch(PDO::FETCH_OBJ)->val;
    }

    static public function exec($sql)
    {
        self::connect();

        self::$dbh->prepare($sql);
        self::$dbh->exec($sql);
    }
}
