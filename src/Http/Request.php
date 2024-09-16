<?php

declare(strict_types=1);

namespace Http;


/**
 *
 * Класс, представляющий HTTP-запрос
 *
 * @package Http
 * @author Alexander Mityukhin <almittt@mail.ru>
 * @date 17.09.2024 3:07
 */
class Request
{
    
    /**
     * HTTP-метод запроса
     * @var string
     */
    private string $method;

    /**
     * URI запроса
     * @var string
     */
    private string $uri;

    /**
     * Заголовки запроса
     * @var array
     */
    private array $headers;

    /**
     * Тело запроса
     * @var string
     */
    private string $body;

    /**
     * Параметры запроса (query params)
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

    /**
     * Создаёт экземпляр Request на основе глобальных данных запроса
     *
     * @return self Возвращает новый экземпляр класса Request
     *
     * Примечание:
     * - HTTP метод берётся из $_SERVER['REQUEST_METHOD']
     * - URI берётся из $_SERVER['REQUEST_URI']
     * - Заголовки берутся с помощью функции getallheaders()
     * - Тело запроса считывается из 'php://input'
     * - Параметры запроса парсятся из $_SERVER['QUERY_STRING'] с использованием функции parse_str()
     */
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