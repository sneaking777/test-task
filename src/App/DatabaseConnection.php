<?php

namespace App;

use PDO;


/**
 *
 * Класс DatabaseConnection предоставляет механизм для создания и использования
 * единственного экземпляра PDO для подключения к базе данных (паттерн Singleton).
 * 
 * @package App
 * @author Alexander Mityukhin <almittt@mail.ru>
 * @date 17.09.2024 3:04
 */
class DatabaseConnection
{
    /**
     * @var DatabaseConnection|null 
     */
    private static ?DatabaseConnection $instance = null;

    /**
     * @var PDO 
     */
    private PDO $pdo;


    /**
     * DatabaseConnection constructor.
     * Инициализирует соединение с базой данных с использованием PDO.
     *
     * @param string $dsn Data Source Name, содержащий информацию для подключения к базе данных.
     * @param string $username Имя пользователя для подключения к базе данных.
     * @param string $password Пароль для подключения к базе данных.
     */
    private function __construct(string $dsn, string $username, string $password)
    {
        $this->pdo = new PDO($dsn, $username, $password);
    }


    /**
     * Метод getInstance реализует паттерн Singleton и возвращает
     * единственный экземпляр класса DatabaseConnection.
     * Если экземпляр не был создан ранее, метод создает его
     * с заданными параметрами подключения.
     *
     * @param string $dsn Data Source Name, содержащий информацию для подключения к базе данных.
     * @param string $username Имя пользователя для подключения к базе данных.
     * @param string $password Пароль для подключения к базе данных.
     * @return DatabaseConnection|null Экземпляр класса DatabaseConnection или null.
     */
    public static function getInstance(string $dsn, string $username, string $password): ?DatabaseConnection
    {
        if (self::$instance === null) {
            self::$instance = new self($dsn, $username, $password);
        }

        return self::$instance;
    }


    /**
     * Возвращает активное соединение с базой данных.
     *
     * @return PDO Объект PDO, представляющий соединение с базой данных.
     */
    public function getConnection(): PDO
    {

        return $this->pdo;
    }
}