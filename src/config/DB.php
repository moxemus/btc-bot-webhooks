<?php

class DB
{
    private string $dbhost = 'localhost';
    private string $dbuser = 'root';
    private string $dbpass = 'root';
    private string $dbname = 'telegram_btc_bot';

    public function connect(): PDO
    {
        $mysql_connect_str = "mysql:host=$this->dbhost;dbname=$this->dbname";
        $dbConnection = new PDO($mysql_connect_str, $this->dbuser, $this->dbpass);
        $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $dbConnection;
    }
}