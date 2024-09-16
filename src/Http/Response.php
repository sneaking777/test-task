<?php

declare(strict_types=1);

namespace Http;


/**
 * Класс Response представляет собой объект HTTP ответа.
 *
 * @package Http
 */
class Response
{
    /**
     * @var int
     */
    private int $status;

    /**
     * @var array
     */
    private array $headers;

    /**
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

    public function withStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value), false);
        }
        echo $this->body;
    }
}