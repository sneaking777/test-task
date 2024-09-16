<?php

namespace App\Services;

use Predis\Client;

class RedisCacheService
{
    private Client $redisClient;

    public function __construct()
    {
        $this->redisClient = new Client([
            'scheme' => 'tcp',
            'host'   => getenv('REDIS_HOST') ?: 'redis-server',
            'port'   => getenv('REDIS_PORT') ?: 6379,
        ]);

    }

    public function get(string $key): ?string
    {
        return $this->redisClient->get($key);
    }

    public function set(string $key, $value, int $ttl = 3600): void
    {
        $this->redisClient->set($key, $value);
        $this->redisClient->expire($key, $ttl);
    }
}
