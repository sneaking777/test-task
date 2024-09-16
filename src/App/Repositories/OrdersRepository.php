<?php

namespace App\Repositories;

use DateTime;
use PDO;
use Exception;

class OrdersRepository
{
    private PDO $dbConnection;

    private const int BATCH_SIZE = 1000; // Количество заказов в одной пачке

    public function __construct($pdo)
    {
        $this->dbConnection = $pdo;
    }


    public function findTotalOrderCount(): int
    {
        $sql = "SELECT COUNT(*) as count FROM orders";
        $stmt = $this->dbConnection->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['count'];
    }


    public function fetchOrders(
        string $startDate = null,
        string $endDate = null,
        string $status = null,
        int $page = 1,
        int $pageSize = 10

    ): array
    {
        $query = "SELECT * FROM orders";
        $conditions = [];
        $params = [];

        if ($startDate) {
            $conditions[] = "order_date >= :startDate";
            $params[':startDate'] = $startDate;
        }

        if ($endDate) {
            $endDateTime = new DateTime($endDate);
            $endDateTime->setTime(23, 59, 59);
            $conditions[] = "order_date <= :endDate";
            $params[':endDate'] = $endDateTime->format('Y-m-d H:i:s');
        }

        if ($status) {
            $conditions[] = "status = :status";
            $params[':status'] = $status;
        }

        if (count($conditions) > 0) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $offset = ($page - 1) * $pageSize;

        // Добавление параметров пагинации напрямую в строку запроса
        $query .= " LIMIT " . $pageSize . " OFFSET " . intval($offset);
        $stmt = $this->dbConnection->prepare($query);

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveOrders(array $orders): void
    {
        if (empty($orders)) {
            return;
        }

        // Разбиваем массив заказов на части по self::BATCH_SIZE
        $orderChunks = array_chunk($orders, self::BATCH_SIZE);

        $this->dbConnection->beginTransaction();
        try {
            foreach ($orderChunks as $chunk) {
                // Построение строки VALUES для всех заказов в текущей части
                $values = [];
                $params = [];

                foreach ($chunk as $order) {
                    $values[] = "(?, ?, ?, ?, ?, ?)";
                    array_push($params, $order['customer_id'], $order['order_date'], $order['status'], $order['total'], $order['created_at'], $order['updated_at']);
                }

                // Объединение всех строк VALUES в один запрос
                $valuesString = implode(',', $values);
                $sql = "INSERT INTO orders (customer_id, order_date, status, total, created_at, updated_at) VALUES $valuesString";

                // Подготовка и выполнение запроса
                $stmt = $this->dbConnection->prepare($sql);
                $stmt->execute($params);
            }
            // Подтверждаем транзакцию, сохраняем все изменения
            $this->dbConnection->commit();
        } catch (Exception $e) {
            // Откатываем транзакцию в случае ошибки
            $this->dbConnection->rollBack();
            throw $e;
        }
    }
}