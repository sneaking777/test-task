<?php

declare(strict_types=1);

namespace Http;


/**
 * Класс Request представляет HTTP-запрос.
 *
 * @package Http
 */
class Request
{
    /**
     * @var string
     */
    private string $method;

    /**
     * @var string
     */
    private string $uri;

    /**
     * @var array
     */
    private array $headers;

    /**
     * @var string
     */
    private string $body;

    /**
     * @var array
     */
    private array $queryParams;


    /**
     * Конструктор
     *
     * @param string $method
     * @param string $uri
     * @param array $headers
     * @param string $body
     * @param array $queryParams
     */
    public function __construct(
        string $method,
        string $uri,
        array $headers = [],
        string $body = '',
        array $queryParams = []
    )
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->headers = $headers;
        $this->body = $body;
        $this->queryParams = $queryParams;
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        $headers = getallheaders();
        $body = file_get_contents('php://input');
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        parse_str($queryString, $queryParams);


        return new self($method, $uri, $headers, $body, $queryParams);
    }

    /**
     * Получение HTTP-метода запроса
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Получение URI запроса
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Получение заголовков запроса
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Получение тела запроса
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Получение всех параметров запроса (query params)
     *
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * Получение определённого параметра запроса по имени
     *
     * @param string $name
     * @return mixed|null
     */
    public function getQueryParam(string $name): mixed
    {
        return $this->queryParams[$name] ?? null;
    }

}