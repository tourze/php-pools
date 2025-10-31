<?php

namespace Utopia\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Utopia\Pools\Connection;
use Utopia\Pools\Exception\PoolConnectionException;
use Utopia\Pools\Pool;

/**
 * @internal
 */
#[CoversClass(Connection::class)]
final class ConnectionTest extends TestCase
{
    /**
     * @var Connection<string>
     */
    protected Connection $object;

    public function testGetID(): void
    {
        $this->object = new Connection('x');
        $this->assertEquals(null, $this->object->getID());

        $this->object->setID('test');

        $this->assertEquals('test', $this->object->getID());
    }

    public function testSetID(): void
    {
        $this->object = new Connection('x');
        $this->assertEquals(null, $this->object->getID());

        $this->object->setID('test');
        $this->assertInstanceOf(Connection::class, $this->object);

        $this->assertEquals('test', $this->object->getID());
    }

    public function testGetResource(): void
    {
        $this->object = new Connection('x');
        $this->assertEquals('x', $this->object->getResource());
    }

    public function testSetResource(): void
    {
        $this->object = new Connection('x');
        $this->assertEquals('x', $this->object->getResource());

        $this->object->setResource('y');
        $this->assertInstanceOf(Connection::class, $this->object);

        $this->assertEquals('y', $this->object->getResource());
    }

    public function testSetPool(): void
    {
        $this->object = new Connection('x');
        $pool = new Pool('test', 1, function () {
            return 'x';
        });

        $this->assertNull($this->object->getPool());
        $this->object->setPool($pool);
        $this->assertInstanceOf(Connection::class, $this->object);
    }

    public function testGetPool(): void
    {
        $this->object = new Connection('x');
        $pool = new Pool('test', 1, function () {
            return 'x';
        });

        $this->assertNull($this->object->getPool());
        $this->object->setPool($pool);
        $this->assertInstanceOf(Connection::class, $this->object);

        $pool = $this->object->getPool();

        if (null === $pool) {
            throw new PoolConnectionException('Pool should never be null here.');
        }

        $this->assertInstanceOf(Pool::class, $pool);
        $this->assertEquals('test', $pool->getName());
    }

    public function testReclaim(): void
    {
        $pool = new Pool('test', 2, function () {
            return 'x';
        });

        $this->assertEquals(2, $pool->count());

        $connection1 = $pool->pop();

        $this->assertEquals(1, $pool->count());

        $connection2 = $pool->pop();

        $this->assertEquals(0, $pool->count());

        $this->assertInstanceOf(Pool::class, $connection1->reclaim());

        $this->assertEquals(1, $pool->count());

        $this->assertInstanceOf(Pool::class, $connection2->reclaim());

        $this->assertEquals(2, $pool->count());
    }

    public function testReclaimException(): void
    {
        $this->object = new Connection('x');
        $this->expectException(PoolConnectionException::class);
        $this->object->reclaim();
    }

    public function testDestroy(): void
    {
        $i = 0;
        $object = new Pool('testDestroy', 2, function () use (&$i) {
            ++$i;

            return $i <= 2 ? 'x' : 'y';
        });

        $this->assertEquals(2, $object->count());

        $connection1 = $object->pop();
        $connection2 = $object->pop();

        $this->assertEquals(0, $object->count());

        $this->assertEquals('x', $connection1->getResource());
        $this->assertEquals('x', $connection2->getResource());

        $connection1->destroy();
        $connection2->destroy();

        $this->assertEquals(2, $object->count());

        $connection1 = $object->pop();
        $connection2 = $object->pop();

        $this->assertEquals(0, $object->count());

        $this->assertEquals('y', $connection1->getResource());
        $this->assertEquals('y', $connection2->getResource());
    }
}
