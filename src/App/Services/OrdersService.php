<?php

namespace App\Services;

use App\Repositories\OrdersRepository;
use DateMalformedStringException;
use Exception;

/**
 * OrdersService - Сервис для работы с заказами.
 *
 * @package App\Services
 * @author Alexander Mityukhin <almittt@mail.ru>
 * @date 17.09.2024 2:38
 */
class OrdersService
{
    /**
     * @var OrdersRepository хранилище заказов
     */
    private OrdersRepository $orderRepository;

    /**
     * Конструктор
     * 
     * @param OrdersRepository $orderRepository
     */
    public function __construct(OrdersRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;

    }


    /**
     * Создание заказов из XML строки
     *
     * @param string $xmlOrders XML заказы в формате строки
     * @return array Массив с информацией о статусе, сообщением и количеством заказов
     * @throws Exception Исключение в случае ошибки сохранения заказов
     */
    public function createXmlOrders(string $xmlOrders): array
    {
        // Парсинг XML строки в объект
        $xmlObject = simplexml_load_string($xmlOrders, "SimpleXMLElement", LIBXML_NOCDATA);

        // Преобразование объекта в JSON строку
        $jsonString = json_encode($xmlObject);
        // Преобразование JSON строки в ассоциативный массив
        $orders = json_decode($jsonString, true)['order'];
        $this->orderRepository->saveOrders($orders);

        return [
            'status' => 'Created',
            'message' => 'Заказы успешно сохранены.',
            'orders_count' => $this->orderRepository->findTotalOrderCount()
        ];

    }
    
    /**
     * Создание заказов из JSON строки
     *
     * @param string $jsonOrders JSON заказы в формате строки
     * @return array Массив с информацией о статусе, сообщением и количеством заказов
     * @throws Exception Исключение в случае ошибки сохранения заказов
     */
    public function createJsonOrders(string $jsonOrders): array
    {
        $orders = json_decode($jsonOrders, true)['orders'];
        $this->orderRepository->saveOrders($orders);

        return [
            'status' => 'Created',
            'message' => 'Заказы успешно сохранены.',
            'orders_count' => $this->orderRepository->findTotalOrderCount()
        ];

    }


    /**
     * Получение заказов с возможностью фильтрации и кэширования
     *
     * @param array $filterParams Параметры фильтрации, включающие:
     *                            - start_date (string|null): Начальная дата
     *                            - end_date (string|null): Конечная дата
     *                            - status (string|null): Статус заказа
     *                            - page (int): Номер страницы
     *                            - page_size (int): Количество заказов на странице
     * @return array Массив с информацией о статусе и найденными заказами
     * @throws DateMalformedStringException Исключение в случае некорректной строки даты
     */
    public function getOrders(array $filterParams): array
    {
        $redis = new RedisCacheService();
        $cacheKey = 'orders:' . md5(json_encode($filterParams));

        // Попытка получить данные из кеша
        $cachedData = $redis->get($cacheKey);
        if ($cachedData) {
            return [
                'status' => 'OK',
                'data' => [
                    'orders' => unserialize($cachedData)
                ]
            ];
        }

        // Выполнение запроса к базе данных
        $startDate = $filterParams['start_date'] ?? null;
        $endDate = $filterParams['end_date'] ?? null;
        $status = $filterParams['status'] ?? null;
        $page = $filterParams['page'] ?? 1;
        $pageSize = $filterParams['page_size'] ?? 10;

        $orders = $this->orderRepository->fetchOrders(
            $startDate,
            $endDate,
            $status,
            $page,
            $pageSize
        );

        // Сохранение данных в кеш
        $redis->set($cacheKey, serialize($orders));

        return [
            'status' => 'OK',
            'data' => [
                'orders' => $orders
            ]
        ];

    }
}