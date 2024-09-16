<?php

namespace App;

use PDO;

class DatabaseConnection
{
    private static $instance = null;
    private PDO $pdo;

    private function __construct(string $dsn, string $username, string $password)
    {
        $this->pdo = new PDO($dsn, $username, $password);
    }

    public static function getInstance(string $dsn, string $username, string $password)
    {
        if (self::$instance === null) {
            self::$instance = new self($dsn, $username, $password);
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {

        return $this->pdo;
    }
}