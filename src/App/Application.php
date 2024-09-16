<?php

declare(strict_types=1);

namespace App;

use App\Services\OrdersService;
use DateMalformedStringException;
use Exception;
use Http\Request;
use Http\Response;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use SimpleXMLElement;


/**
 * Класс Application
 *
 * Этот класс обрабатывает HTTP-запросы и ответы, настраивает логирование и маршрутизирует запросы
 * к соответствующему методу обработки для обработки заказов.
 *
 * @package App
 * @author Alexander Mityukhin <almittt@mail.ru>
 * @date 17.09.2024 2:46
 */
class Application
{
    /**
     * @var OrdersService
     */
    private OrdersService $service;

    /**
     * @var Logger 
     */
    private Logger $logger;

    /**
     * @var Request 
     */
    private Request $request;


    /**
     * Конструктор
     * 
     * @throws Exception
     */
    public function __construct()
    {
        $container = ContainerManager::getContainer();

        // Настройка логгера
        $logsDir = realpath(__DIR__ . '/../../storage/logs');
        $this->logger = new Logger('orders');
        $this->logger->pushHandler(new StreamHandler($logsDir . '/orders.log', Level::Info->value));
        $this->service = $container->make('OrdersService');
    }

    /**
     * Обработчик HTTP-запросов
     *
     * Метод принимает HTTP-запрос, разбирает URI и метод запроса, маршрутизирует
     * запрос к соответствующему методу для обработки и записывает информацию о запросе
     * в лог. После обработки запроса отправляет ответ клиенту.
     *
     * @param Request $request HTTP-запрос из клиента.
     * @return void
     * @throws DateMalformedStringException
     */
    public function handle(Request $request): void
    {
        $startTime = microtime(true);  // Время начала запроса

        $path = parse_url($request->getUri(), PHP_URL_PATH);
        $method = $request->getMethod();
        $queryParams = $request->getQueryParams();
        $this->request = $request;
        $response = match ($path) {
            '/api/orders/xml' => $this->handleOrders($method, 'xml', $queryParams),
            '/api/orders/json' => $this->handleOrders($method, 'json', $queryParams),
            default => $this->sendResponse(['error' => 'Не найдено'], 404),
        };

        $endTime = microtime(true);  // Время окончания запроса
        $executionTime = $endTime - $startTime;  // Время выполнения запроса

        $this->logger->info('Запрос обработан', [
            'path' => $path,
            'method' => $method,
            'execution_time' => $executionTime,
        ]);

        $response->send();
    }

    /**
     * Метод `handleOrders` обрабатывает запросы на получение и создание заказов в заданном формате.
     *
     * @param string $method Метод HTTP-запроса (GET или POST).
     * @param string $format Формат ответа (json или xml).
     * @param array $queryParams Параметры запроса.
     * @return Response Ответ, содержащий результат обработки запроса.
     * @throws DateMalformedStringException
     * @throws Exception
     */
    private function handleOrders(string $method, string $format, array $queryParams): Response
    {
        return match ($method) {
            'GET' => $this->getOrders($format, $queryParams),
            'POST' => $this->postOrders($format),
            default => $this->sendResponse(['error' => 'Method Not Allowed'], 405),
        };
    }

    /**
     * Метод `handleOrders` обрабатывает запросы на получение и создание заказов в заданном формате.
     *
     * В зависимости от метода HTTP-запроса (`GET` или `POST`), перенаправляет запрос
     * к соответствующему методу обработки.
     *
     * @param string $format Формат ответа (json или xml).
     * @param array $queryParams Параметры запроса.
     * @return Response Ответ, содержащий результат обработки запроса.
     * @throws DateMalformedStringException
     */
    private function getOrders(string $format, array $queryParams): Response
    {

        $response = match ($format) {
            'json', 'xml' => $this->service->getOrders($queryParams)
        };
        $this->logger->info(
            "GET /api/orders/$format",
            [
                'status' => 'success',
                'request' => $this->request->getUri(),
                'response' => $response
            ],
        );

        return $this->sendResponse($response, 200, $format);
    }
    
    /**
     * Метод `postOrders` обрабатывает HTTP POST-запросы для создания заказов в определенном формате.
     *
     * Метод принимает данные заказа из тела запроса, создаёт заказ с использованием сервиса OrdersService,
     * и отправляет ответ с результатом создания заказа.
     * Логирует успешные запросы с указанием URI запроса и ответа.
     *
     * @param string $format Формат данных (json или xml).
     * @return Response Ответ, содержащий результат обработки запроса.
     * @throws Exception В случае ошибок при обработке запроса.
     */
    private function postOrders(string $format): Response
    {
        $inputData = file_get_contents('php://input');
        $response = match ($format) {
            'json' => $this->service->createJsonOrders($inputData),
            'xml' => $this->service->createXmlOrders($inputData)
        };
        $this->logger->info(
            "POST /api/orders/$format",
            [
                'status' => 'success',
                'request' => $this->request->getUri(),
                'response' => $response
            ],
        );

        return $this->sendResponse($response, 201, $format);
    }
    
    /**
     * Формирует и отправляет HTTP-ответ клиенту.
     *
     * Метод принимает данные для ответа в виде массива, статусный код ответа и
     * формат ответа (json или xml). На основе этих данных формирует тело ответа
     * в указанном формате и устанавливает соответствующий заголовок Content-Type.
     *
     * @param array $data Данные для включения в тело ответа.
     * @param int $status Код состояния HTTP-ответа (по умолчанию 200).
     * @param string $format Формат ответа (по умолчанию json).
     * @return Response Ответ, содержащий данные в указанном формате.
     */
    private function sendResponse(array $data, int $status = 200, string $format = 'json'): Response
    {
        if ($format === 'xml') {
            $body = $this->arrayToXml($data);
            $contentType = 'application/xml';
        } else {
            $body = json_encode($data);
            $contentType = 'application/json';
        }

        $response = new Response($status, [], $body);
        $response->withHeader('Content-Type', $contentType);

        return $response;
    }
    
    /**
     * Преобразует массив в формат XML.
     *
     * Метод принимает массив данных и рекурсивно преобразует его в формат XML.
     * Если начальный элемент XML не представлен, он создается с тегом <data>.
     * Под массивы обрабатываются рекурсивно, а каждый элемент массива
     * включается в XML с соответствующим ключом как тегом.
     * Если значение является числом, оно преобразуется в строку и используется
     * как содержимое тэга.
     *
     * @param array $data Входной массив данных для преобразования.
     * @param SimpleXMLElement|null $xmlData Изначальный элемент XML,
     * если он уже существует, или null для создания нового элемента.
     * @return string Возвращает результирующий XML в виде строки.
     */
    private function arrayToXml(array $data, SimpleXMLElement $xmlData = null): string
    {
        if ($xmlData === null) {
            $xmlData = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><data value=""></data>');
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'order';
                }
                $subNode = $xmlData->addChild($key);
                $this->arrayToXml($value, $subNode);
            } else {
                $xmlData->addChild("$key", htmlspecialchars(
                    "$value", ENT_QUOTES | ENT_XML1, 'UTF-8'));
            }
        }

        return $xmlData->asXML();
    }
}