-- Создание таблицы заказов
-- Таблица для хранения информации о заказах
CREATE TABLE orders
(
    order_id    INT PRIMARY KEY AUTO_INCREMENT COMMENT 'Уникальный автоинкрементный идентификатор для каждого заказа',
    customer_id INT            NOT NULL COMMENT 'Идентификатор клиента, разместившего заказ',
    order_date  TIMESTAMP      NOT NULL COMMENT 'Дата и время размещения заказа',
    status      VARCHAR(50)    NOT NULL COMMENT 'Статус заказа (например, ''в ожидании'', ''завершен'')',
    total       DECIMAL(10, 2) NOT NULL COMMENT 'Общая сумма заказа',
    created_at  DATETIME       NOT NULL COMMENT 'Временная отметка создания записи заказа',
    updated_at  DATETIME       NULL COMMENT 'Временная отметка последнего обновления записи заказа',
    INDEX idx_customer_id (customer_id)
)
-- Добавление комментария к таблице
    COMMENT = 'Таблица для хранения информации о заказах'
-- Создание таблицы с использованием движка InnoDB и кодировки символов utf8
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4;