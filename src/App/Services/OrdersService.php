<?php

namespace App\Services;

use App\Repositories\OrdersRepository;

class OrdersService
{
    private OrdersRepository $orderRepository;


    public function __construct(OrdersRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;

    }

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
     * @param array $filterParams
     * @return array
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