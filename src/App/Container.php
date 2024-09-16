<?php

namespace App;

use Exception;

/**
 * Класс Container
 * 
 * Этот класс предоставляет базовую реализацию контейнера инверсии управления (IoC).
 * Он позволяет связывать пары ключ-значение, где значение является резолвером
 * (функцией, которая генерирует объект). Класс поддерживает связывание как общих
 *  экземпляров (singleton), так и не общих экземпляров.
 * 
 * @package App
 * @author Alexander Mityukhin <almittt@mail.ru>
 * @date 17.09.2024 2:55
 */
class Container
{
    /**
     * @var array 
     */
    private array $bindings = [];

    /**
     * @var array 
     */
    private array $instances = [];

    /**
     * Осуществляет связывание заданного ключа с резолвером для создания общего экземпляра (singleton).
     * Если контейнер уже содержит экземпляр, он будет возвращен. В противном случае, будет создан новый экземпляр.
     *
     * @param string $key Ключ, с которым связывается резолвер
     * @param object $resolver Резолвер, который генерирует объект
     * @return void
     */
    public function singleton(string $key, object $resolver): void
    {
        $this->bindings[$key] = $resolver;
        $this->instances[$key] = null;
    }

    
    /**
     * Связывает заданный ключ с резолвером для создания не общих экземпляров.
     * В отличие от метода singleton, каждый вызов make будет создавать новый экземпляр.
     *
     * @param string $key Ключ, с которым связывается резолвер
     * @param object $resolver Резолвер, который генерирует объект
     * @return void
     */
    public function bind(string $key, object $resolver): void
    {
        $this->bindings[$key] = $resolver;
    }

    /**
     * Создает и возвращает экземпляр объекта, связанного с заданным ключом.
     * Если экземпляр существует, он возвращается. В противном случае
     * создается новый экземпляр, используя резолвер, связанный с ключом.
     * В случае использования метода singleton результат будет сохранен
     * и повторно использован.
     *
     * @param string $key Ключ, с которым связан резолвер
     * @return mixed Экземпляр объекта
     * @throws Exception Если связанный резолвер не найден
     */
    public function make(string $key): mixed
    {
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        if (!isset($this->bindings[$key])) {
            throw new Exception("No binding found for key: $key");
        }

        $resolver = $this->bindings[$key];
        $object = $resolver($this);

        if (array_key_exists($key, $this->instances)) {
            $this->instances[$key] = $object;
        }

        return $object;
    }

}