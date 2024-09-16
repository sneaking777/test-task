<?php

declare(strict_types=1);

namespace App;

use App\Services\OrdersService;
use Http\Request;
use Http\Response;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use SimpleXMLElement;


/**
 * Класс Application отвечает за обработку HTTP-запросов.
 *
 * @package App
 */
class Application
{
    /**
     * @var OrdersService
     */
    private OrdersService $service;

    private Logger $logger;

    private Request $request;


    public function __construct()
    {
        $container = ContainerManager::getContainer();

        // Настройка логгера
        $logsDir = realpath(__DIR__ . '/../../storage/logs');
        $this->logger = new Logger('orders');
        $this->logger->pushHandler(new StreamHandler($logsDir . '/orders.log', Level::Info->value));
        $this->service = $container->make('OrdersService');
    }

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

    private function handleOrders(string $method, string $format, array $queryParams): Response
    {
        return match ($method) {
            'GET' => $this->getOrders($format, $queryParams),
            'POST' => $this->postOrders($format),
            default => $this->sendResponse(['error' => 'Метод не разрешён'], 405),
        };
    }

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

    private function arrayToXml(array $data, SimpleXMLElement $xmlData = null): string
    {
        if ($xmlData === null) {
            $xmlData = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><data></data>');
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