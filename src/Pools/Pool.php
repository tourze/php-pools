<?php

namespace Utopia\Pools;

use Utopia\Pools\Exception\PoolConnectionException;
use Utopia\Pools\Exception\PoolEmptyException;

/**
 * @template TResource
 */
class Pool
{
    protected string $name;

    protected int $size = 0;

    /**
     * @var callable(): TResource
     */
    protected $init;

    protected int $reconnectAttempts = 3;

    protected int $reconnectSleep = 1; // seconds

    protected int $retryAttempts = 3;

    protected int $retrySleep = 1; // seconds

    /**
     * @var array<Connection<TResource>|true>
     */
    protected array $pool = [];

    /**
     * @var array<string, Connection<TResource>>
     */
    protected array $active = [];

    /**
     * @param callable(): TResource $init
     */
    public function __construct(string $name, int $size, callable $init)
    {
        $this->name = $name;
        $this->size = $size;
        $this->init = $init;
        $this->pool = array_fill(0, $size, true);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getReconnectAttempts(): int
    {
        return $this->reconnectAttempts;
    }

    public function setReconnectAttempts(int $reconnectAttempts): void
    {
        $this->reconnectAttempts = $reconnectAttempts;
    }

    public function getReconnectSleep(): int
    {
        return $this->reconnectSleep;
    }

    public function setReconnectSleep(int $reconnectSleep): void
    {
        $this->reconnectSleep = $reconnectSleep;
    }

    public function getRetryAttempts(): int
    {
        return $this->retryAttempts;
    }

    public function setRetryAttempts(int $retryAttempts): void
    {
        $this->retryAttempts = $retryAttempts;
    }

    public function getRetrySleep(): int
    {
        return $this->retrySleep;
    }

    public function setRetrySleep(int $retrySleep): void
    {
        $this->retrySleep = $retrySleep;
    }

    /**
     * 使用管理的连接执行回调函数
     *
     * @template T
     *
     * @param callable(TResource): T $callback Function that receives the connection resource
     *
     * @return T Return value from the callback
     */
    public function use(callable $callback): mixed
    {
        $start = microtime(true);
        $connection = null;
        try {
            $connection = $this->pop();

            return $callback($connection->getResource());
        } finally {
            if (null !== $connection) {
                $this->reclaim($connection);
            }
        }
    }

    /**
     * 摘要：
     *  1. 尝试从连接池中获取连接
     *  2. 如果没有可用连接，等待连接释放
     *  3. 如果仍然没有可用连接，抛出异常
     *  4. 如果有可用连接，返回该连接
     *
     * @return Connection<TResource>
     *
     * @throws PoolEmptyException
     *
     * @internal please migrate to `use`
     */
    public function pop(): Connection
    {
        try {
            $connection = $this->retrieveConnectionFromPool();
            $connection = $this->ensureConnectionIsCreated($connection);

            return $this->prepareConnectionForUse($connection);
        } finally {
            $this->recordPoolTelemetry();
        }
    }

    /**
     * @return Connection<TResource>|true|null
     *
     * @throws PoolEmptyException
     */
    private function retrieveConnectionFromPool(): Connection|bool|null
    {
        $attempts = 0;

        do {
            ++$attempts;
            $connection = array_pop($this->pool);

            if (is_null($connection)) {
                if ($attempts >= $this->getRetryAttempts()) {
                    throw new PoolEmptyException("Pool '{$this->name}' is empty (size {$this->size})");
                }

                sleep($this->getRetrySleep());
            } else {
                return $connection;
            }
        } while ($attempts < $this->getRetryAttempts());

        return null;
    }

    /**
     * @param Connection<TResource>|true|null $connection
     *
     * @return Connection<TResource>
     *
     * @throws PoolConnectionException
     */
    private function ensureConnectionIsCreated(Connection|bool|null $connection): Connection
    {
        if (true === $connection) {
            return $this->createNewConnection();
        }

        if ($connection instanceof Connection) {
            return $connection;
        }

        throw new PoolConnectionException('Failed to get a connection from the pool');
    }

    /**
     * @return Connection<TResource>
     *
     * @throws PoolConnectionException
     */
    private function createNewConnection(): Connection
    {
        $attempts = 0;

        do {
            try {
                ++$attempts;

                return new Connection(($this->init)());
            } catch (\Throwable $e) {
                if ($attempts >= $this->getReconnectAttempts()) {
                    throw new PoolConnectionException('Failed to create connection: ' . $e->getMessage());
                }
                sleep($this->getReconnectSleep());
            }
        } while ($attempts < $this->getReconnectAttempts());

        throw new PoolConnectionException('Failed to create connection after all attempts');
    }

    /**
     * @param Connection<TResource> $connection
     *
     * @return Connection<TResource>
     */
    private function prepareConnectionForUse(Connection $connection): Connection
    {
        if ('' === $connection->getID()) {
            $connection->setID($this->getName() . '-' . uniqid());
        }

        $connection->setPool($this);
        $this->active[$connection->getID()] = $connection;

        return $connection;
    }

    /**
     * @param Connection<TResource> $connection
     */
    public function push(Connection $connection): static
    {
        try {
            $this->pool[] = $connection;
            unset($this->active[$connection->getID()]);

            return $this;
        } finally {
            $this->recordPoolTelemetry();
        }
    }

    public function count(): int
    {
        return count($this->pool);
    }

    /**
     * @param Connection<TResource>|null $connection
     */
    public function reclaim(?Connection $connection = null): static
    {
        if (null !== $connection) {
            $this->push($connection);

            return $this;
        }

        foreach ($this->active as $activeConnection) {
            $this->push($activeConnection);
        }

        return $this;
    }

    /**
     * @param Connection<TResource>|null $connection
     */
    public function destroy(?Connection $connection = null): static
    {
        try {
            if (null !== $connection) {
                $this->pool[] = true;
                unset($this->active[$connection->getID()]);

                return $this;
            }

            foreach ($this->active as $activeConnection) {
                $this->pool[] = true;
                unset($this->active[$activeConnection->getID()]);
            }

            return $this;
        } finally {
            $this->recordPoolTelemetry();
        }
    }

    public function isEmpty(): bool
    {
        return [] === $this->pool;
    }

    public function isFull(): bool
    {
        return count($this->pool) === $this->size;
    }

    private function recordPoolTelemetry(): void
    {
    }
}
