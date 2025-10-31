<?php

namespace Utopia\Pools;

use Utopia\Pools\Exception\PoolConnectionException;

/**
 * @template TResource
 */
class Connection
{
    protected string $id = '';

    /**
     * @var Pool<TResource>|null
     */
    protected ?Pool $pool = null;

    /**
     * @param TResource $resource
     */
    public function __construct(protected mixed $resource)
    {
    }

    public function getID(): string
    {
        return $this->id;
    }

    public function setID(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return TResource
     */
    public function getResource(): mixed
    {
        return $this->resource;
    }

    /**
     * @param TResource $resource
     */
    public function setResource(mixed $resource): void
    {
        $this->resource = $resource;
    }

    /**
     * @return Pool<TResource>|null
     */
    public function getPool(): ?Pool
    {
        return $this->pool;
    }

    /**
     * @param Pool<TResource> $pool
     */
    public function setPool(Pool $pool): void
    {
        $this->pool = $pool;
    }

    /**
     * @return Pool<TResource>
     *
     * @throws PoolConnectionException
     */
    public function reclaim(): Pool
    {
        if (null === $this->pool) {
            throw new PoolConnectionException('You cannot reclaim connection that does not have a pool.');
        }

        return $this->pool->reclaim($this);
    }

    /**
     * @return Pool<TResource>
     *
     * @throws PoolConnectionException
     */
    public function destroy(): Pool
    {
        if (null === $this->pool) {
            throw new PoolConnectionException('You cannot destroy connection that does not have a pool.');
        }

        return $this->pool->destroy($this);
    }
}
