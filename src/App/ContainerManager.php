<?php

namespace App;

use App\Repositories\OrdersRepository;
use App\Services\OrdersService;

class ContainerManager
{
    private static ?Container $container = null;

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