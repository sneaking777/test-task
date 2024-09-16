<?php

namespace App;

use Exception;

class Container
{
    private array $bindings = [];
    private array $instances = [];

    public function bind(string $key, object $resolver)
    {
        $this->bindings[$key] = $resolver;
    }

    public function singleton(string $key, object $resolver)
    {
        $this->bindings[$key] = $resolver;
        $this->instances[$key] = null;
    }

    public function make(string $key)
    {
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        if (!isset($this->bindings[$key])) {
            throw new Exception("No binding found for key: {$key}");
        }

        $resolver = $this->bindings[$key];
        $object = $resolver($this);

        if (array_key_exists($key, $this->instances)) {
            $this->instances[$key] = $object;
        }

        return $object;
    }

}