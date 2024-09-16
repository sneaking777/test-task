<?php

namespace App\Repositories;

use DateMalformedStringException;
use DateTime;
use PDO;
use Exception;


/**
 * Класс для работы с хранилищем заказов.
 * 
 * @package App\Repositories
 */
class OrdersRepository
{
    /**
     * @var PDO $pdo Объект PDO для подключения к базе данных.
     */
    private PDO $dbConnection;

    private const int BATCH_SIZE = 1000; // Количество заказов в одной пачке

    /**
     * Конструктор
     *
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->dbConnection = $pdo;
    }

    /**
     * Метод для получения общего количества заказов.
     *
     * Этот метод выполняет SQL-запрос для подсчета всех записей в таблице заказов
     * и возвращает их количество в виде целого числа.
     *
     * @return int Общее количество заказов.
     */
    public function findTotalOrderCount(): int
    {
        $sql = "SELECT COUNT(*) as count FROM orders";
        $stmt = $this->dbConnection->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['count'];
    }

    
    /**
     * Получение списка заказов с учетом фильтров и пагинации.
     *
     * Этот метод позволяет получать заказы, отфильтрованные по дате начала и конца,
     * статусу, а также с использованием пагинации. Лимит и смещение результатов
     * задается параметрами $page и $pageSize.
     * Если дата окончания указана, она автоматически устанавливается на конец дня.
     *
     * @param string|null $startDate Дата начала фильтрации (включительно) в формате 'Y-m-d'.
     * @param string|null $endDate Дата окончания фильтрации (включительно) в формате 'Y-m-d'.
     * @param string|null $status Фильтр по статусу заказа.
     * @param int $page Номер страницы для пагинации (по умолчанию 1).
     * @param int $pageSize Количество заказов на страницу (по умолчанию 10).
     * @return array Массив заказов, подходящих под заданные критерии.
     * @throws DateMalformedStringException Исключение, если переданы некорректные даты.
     */
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

    /**
     * Сохранение заказов в базе данных.
     *
     * Этот метод принимает массив заказов и сохраняет их в базе данных.
     * Заказы делятся на части по BATCH_SIZE, и каждая часть вставляется в таблицу orders.
     * Используется транзакция для обеспечения целостности данных: если хотя бы одна вставка
     * не удалась, все изменения отменяются.
     *
     * @param array $orders Массив заказов для сохранения.
     * @return void
     * @throws Exception Исключение, если произошла ошибка при сохранении заказов.
     */
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
                    array_push(
                        $params,
                        $order['customer_id'],
                        $order['order_date'],
                        $order['status'],
                        $order['total'],
                        $order['created_at'],
                        $order['updated_at']);
                }

                // Объединение всех строк VALUES в один запрос
                $valuesString = implode(',', $values);
                $sql = "INSERT INTO orders (customer_id, order_date, status, total, created_at, updated_at) 
                        VALUES $valuesString";

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