<?php

declare(strict_types=1);

namespace Http;


/**
 * Класс Response представляет собой объект HTTP ответа.
 *
 * @package Http
 * @author Alexander Mityukhin <almittt@mail.ru>
 * @date 17.09.2024 3:10
 */
class Response
{
    
    /**
     * HTTP статус ответа
     *
     * @var int
     */
    private int $status;

    /**
     * HTTP заголовки ответа
     *
     * @var array
     */
    private array $headers;

    /**
     * Тело HTTP ответа
     *
     * @var string
     */
    private string $body;

    /**
     * Конструктор
     *
     * @param int $status
     * @param array $headers
     * @param string $body
     */
    public function __construct(int $status = 200, array $headers = [], string $body = '')
    {
        $this->status = $status;
        $this->headers = $headers;
        $this->body = $body;
    }
    
    /**
     * Метод withStatus устанавливает статус ответа.
     *
     * @param int $status HTTP статус код
     * @return $this
     */
    public function withStatus(int $status): self
    {
        $this->status = $status;
        
        return $this;
    }
    
    /**
     * Добавляет или обновляет HTTP заголовок ответа.
     *
     * @param string $name Имя заголовка
     * @param string $value Значение заголовка
     * @return $this
     */
    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        
        return $this;
    }
    
    /**
     * Возвращает HTTP статус код ответа
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->status;
    }


    /**
     * Возвращает HTTP заголовки ответа
     *
     * Этот метод возвращает массив заголовков,
     * которые были установлены для HTTP ответа.
     *
     * @return array Массив заголовков, где ключи - названия заголовков, а значения - их соответствующие значения
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }


    /**
     * Возвращает тело HTTP ответа.
     *
     * Этот метод возвращает содержимое тела HTTP ответа в виде строки.
     *
     * @return string Содержимое тела HTTP ответа
     */
    public function getBody(): string
    {
        return $this->body;
    }


    /**
     * Отправляет HTTP ответ клиенту.
     *
     * Этот метод устанавливает HTTP статус код, добавляет заголовки и выводит тело ответа.
     *
     * @return void
     */
    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value), false);
        }
        echo $this->body;
    }
}