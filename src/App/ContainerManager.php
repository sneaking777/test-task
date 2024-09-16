<?php

namespace App;

use App\Repositories\OrdersRepository;
use App\Services\OrdersService;


/**
 *
 * Класс для управления контейнером зависимостей.
 *
 * @package App
 *
 * @author Alexander Mityukhin <almittt@mail.ru>
 * @date 17.09.2024 3:03
 */
class ContainerManager
{
    /**
     * @var Container|null 
     */
    private static ?Container $container = null;


    /**
     * Возвращает экземпляр контейнера зависимостей.
     * Если контейнер ещё не был инициализирован, он создаётся и
     * происходит инициализация всех необходимых сервисов.
     *
     * @return Container Экземпляр контейнера зависимостей.
     */
    public static function getContainer(): Container
    {
        if (null === self::$container) {
            self::$container = new Container();
            // Инициализация сервисов
            self::$container->singleton('DatabaseConnection', function () {
                return DatabaseConnection::getInstance(
                    'mysql:host=mysql-server;dbname=test_task',
                    'alex',
                    '123'
                )->getConnection();
            });

            self::$container->bind('OrdersRepository', function ($container) {
                return new OrdersRepository($container->make('DatabaseConnection'));
            });

            self::$container->bind('OrdersService', function ($container) {
                return new OrdersService($container->make('OrdersRepository'));
            });
        }

        return self::$container;
    }
}