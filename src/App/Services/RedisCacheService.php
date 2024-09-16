<?php

namespace App\Services;

use Predis\Client;

/**
 * Класс RedisCacheService
 *
 * Этот класс предоставляет сервисный слой для взаимодействия с Redis с использованием клиента Predis.
 * Он включает методы для получения и установки данных в кеш Redis.
 * 
 * @package App\Services
 * @author Alexander Mityukhin <almittt@mail.ru>
 * @date 17.09.2024 2:41
 */
class RedisCacheService
{
    /**
     * @var Client 
     */
    private Client $redisClient;

    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->redisClient = new Client([
            'scheme' => 'tcp',
            'host'   => getenv('REDIS_HOST') ?: 'redis-server',
            'port'   => getenv('REDIS_PORT') ?: 6379,
        ]);

    }
    
    /**
     * Возвращает значение из Redis по указанному ключу
     *
     * @param string $key Ключ для поиска значения в Redis.
     * @return string|null Значение, соответствующее ключу, или null, если ключ не найден.
     */
    public function get(string $key): ?string
    {
        return $this->redisClient->get($key);
    }


    /**
     * Устанавливает значение в Redis по указанному ключу с заданным временем жизни (TTL).
     *
     * @param string $key Ключ, по которому будет сохранено значение.
     * @param mixed $value Значение, которое необходимо сохранить.
     * @param int $ttl Время жизни ключа в секундах (по умолчанию 3600 секунд).
     * @return void
     */
    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $this->redisClient->set($key, $value);
        $this->redisClient->expire($key, $ttl);
    }
}
