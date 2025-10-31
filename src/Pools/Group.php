<?php

namespace Utopia\Pools;

use Utopia\Pools\Exception\PoolInvalidUsageException;
use Utopia\Pools\Exception\PoolNotFoundException;

class Group
{
    /**
     * @var array<Pool<covariant mixed>>
     */
    protected array $pools = [];

    /**
     * @param Pool<covariant mixed> $pool
     */
    public function add(Pool $pool): static
    {
        $this->pools[$pool->getName()] = $pool;

        return $this;
    }

    /**
     * @return Pool<covariant mixed>
     *
     * @throws PoolNotFoundException
     */
    public function get(string $name): Pool
    {
        return $this->pools[$name] ?? throw new PoolNotFoundException("Pool '{$name}' not found");
    }

    public function remove(string $name): static
    {
        unset($this->pools[$name]);

        return $this;
    }

    public function reclaim(): static
    {
        foreach ($this->pools as $pool) {
            $pool->reclaim();
        }

        return $this;
    }

    /**
     * 使用管理的连接执行回调函数
     *
     * @template TReturn
     *
     * @param array<string>               $names    Name of resources
     * @param callable(mixed...): TReturn $callback Function that receives the connection resources
     *
     * @return TReturn Return value from the callback
     *
     * @throws PoolInvalidUsageException
     */
    public function use(array $names, callable $callback): mixed
    {
        if ([] === $names) {
            throw new PoolInvalidUsageException('Cannot use with empty names');
        }

        return $this->useInternal($names, $callback);
    }

    /**
     * `use` 方法的内部递归回调函数
     *
     * @template TReturn
     *
     * @param array<string>               $names     Name of resources
     * @param callable(mixed...): TReturn $callback  Function that receives the connection resources
     * @param array<mixed>                $resources
     *
     * @return TReturn
     *
     * @throws PoolNotFoundException
     */
    private function useInternal(array $names, callable $callback, array $resources = []): mixed
    {
        if ([] === $names) {
            return $callback(...$resources);
        }

        return $this
            ->get(array_shift($names))
            ->use(fn ($resource) => $this->useInternal($names, $callback, array_merge($resources, [$resource])))
        ;
    }

    public function setReconnectAttempts(int $reconnectAttempts): void
    {
        foreach ($this->pools as $pool) {
            $pool->setReconnectAttempts($reconnectAttempts);
        }
    }

    public function setReconnectSleep(int $reconnectSleep): void
    {
        foreach ($this->pools as $pool) {
            $pool->setReconnectSleep($reconnectSleep);
        }
    }
}
