<?php

namespace Utopia\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Utopia\Pools\Connection;
use Utopia\Pools\Exception\PoolEmptyException;
use Utopia\Pools\Pool;

/**
 * @internal
 */
#[CoversClass(Pool::class)]
final class PoolTest extends TestCase
{
    /**
     * @var Pool<string>
     */
    protected Pool $object;

    public function testGetName(): void
    {
        $this->object = new Pool('test', 5, function () {
            return 'x';
        });
        $this->assertEquals('test', $this->object->getName());
    }

    public function testGetSize(): void
    {
        $this->object = new Pool('test', 5, function () {
            return 'x';
        });
        $this->assertEquals(5, $this->object->getSize());
    }

    public function testGetReconnectAttempts(): void
    {
        $this->object = new Pool('test', 5, function () {
            return 'x';
        });
        $this->assertEquals(3, $this->object->getReconnectAttempts());
    }

    public function testSetReconnectAttempts(): void
    {
        $this->object = new Pool('test', 5, function () {
            return 'x';
        });
        $this->assertEquals(3, $this->object->getReconnectAttempts());

        $this->object->setReconnectAttempts(20);

        $this->assertEquals(20, $this->object->getReconnectAttempts());
    }

    public function testGetReconnectSleep(): void
    {
        $this->object = new Pool('test', 5, function () {
            return 'x';
        });
        $this->assertEquals(1, $this->object->getReconnectSleep());
    }

    public function testSetReconnectSleep(): void
    {
        $this->object = new Pool('test', 5, function () {
            return 'x';
        });
        $this->assertEquals(1, $this->object->getReconnectSleep());

        $this->object->setReconnectSleep(20);

        $this->assertEquals(20, $this->object->getReconnectSleep());
    }

    public function testGetRetryAttempts(): void
    {
        $this->object = new Pool('test', 5, function () {
            return 'x';
        });
        $this->assertEquals(3, $this->object->getRetryAttempts());
    }

    public function testSetRetryAttempts(): void
    {
        $this->object = new Pool('test', 5, function () {
            return 'x';
        });
        $this->assertEquals(3, $this->object->getRetryAttempts());

        $this->object->setRetryAttempts(20);

        $this->assertEquals(20, $this->object->getRetryAttempts());
    }

    public function testGetRetrySleep(): void
    {
        $this->object = new Pool('test', 5, function () {
            return 'x';
        });
        $this->assertEquals(1, $this->object->getRetrySleep());
    }

    public function testSetRetrySleep(): void
    {
        $this->object = new Pool('test', 5, function () {
            return 'x';
        });
        $this->assertEquals(1, $this->object->getRetrySleep());

        $this->object->setRetrySleep(20);

        $this->assertEquals(20, $this->object->getRetrySleep());
    }

    public function testPop(): void
    {
        $this->object = new Pool('test', 5, function () {
            return 'x';
        });
        $this->assertEquals(5, $this->object->count());

        $connection = $this->object->pop();

        $this->assertEquals(4, $this->object->count());

        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertEquals('x', $connection->getResource());

        // Pool should be empty
        $this->expectException(PoolEmptyException::class);

        $this->assertInstanceOf(Connection::class, $this->object->pop());
        $this->assertInstanceOf(Connection::class, $this->object->pop());
        $this->assertInstanceOf(Connection::class, $this->object->pop());
        $this->assertInstanceOf(Connection::class, $this->object->pop());
        $this->assertInstanceOf(Connection::class, $this->object->pop());
    }

    public function testUse(): void
    {
        $this->object = new Pool('test', 5, function () {
            return 'x';
        });
        $this->assertEquals(5, $this->object->count());
        $this->object->use(function ($resource): void {
            $this->assertEquals(4, $this->object->count());
            $this->assertEquals('x', $resource);
        });

        $this->assertEquals(5, $this->object->count());
    }

    public function testPush(): void
    {
        $this->object = new Pool('test', 5, function () {
            return 'x';
        });
        $this->assertEquals(5, $this->object->count());

        $connection = $this->object->pop();

        $this->assertEquals(4, $this->object->count());

        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertEquals('x', $connection->getResource());

        $this->assertInstanceOf(Pool::class, $this->object->push($connection));

        $this->assertEquals(5, $this->object->count());
    }

    public function testCount(): void
    {
        $this->object = new Pool('test', 5, function () {
            return 'x';
        });
        $this->assertEquals(5, $this->object->count());

        $connection = $this->object->pop();

        $this->assertEquals(4, $this->object->count());

        $this->object->push($connection);

        $this->assertEquals(5, $this->object->count());
    }

    public function testReclaim(): void
    {
        $this->object = new Pool('test', 5, function () {
            return 'x';
        });
        $this->assertEquals(5, $this->object->count());

        $this->object->pop();
        $this->object->pop();
        $this->object->pop();

        $this->assertEquals(2, $this->object->count());

        $this->object->reclaim();

        $this->assertEquals(5, $this->object->count());
    }

    public function testIsEmpty(): void
    {
        $this->object = new Pool('test', 5, function () {
            return 'x';
        });
        $this->object->pop();
        $this->object->pop();
        $this->object->pop();
        $this->object->pop();
        $this->object->pop();

        $this->assertEquals(true, $this->object->isEmpty());
    }

    public function testIsFull(): void
    {
        $this->object = new Pool('test', 5, function () {
            return 'x';
        });
        $this->assertEquals(true, $this->object->isFull());

        $connection = $this->object->pop();

        $this->assertEquals(false, $this->object->isFull());

        $this->object->push($connection);

        $this->assertEquals(true, $this->object->isFull());

        $this->object->pop();
        $this->object->pop();
        $this->object->pop();
        $this->object->pop();
        $this->object->pop();

        $this->assertEquals(false, $this->object->isFull());

        $this->object->reclaim();

        $this->assertEquals(true, $this->object->isFull());

        $this->object->pop();
        $this->object->pop();
        $this->object->pop();
        $this->object->pop();
        $this->object->pop();

        $this->assertEquals(false, $this->object->isFull());
    }

    public function testRetry(): void
    {
        $this->object = new Pool('test', 5, function () {
            return 'x';
        });
        $this->object->setReconnectAttempts(2);
        $this->object->setReconnectSleep(2);

        $this->object->pop();
        $this->object->pop();
        $this->object->pop();
        $this->object->pop();
        $this->object->pop();

        // Pool should be empty
        $this->expectException(PoolEmptyException::class);

        $timeStart = \time();
        $this->object->pop();
        $timeEnd = \time();

        $timeDiff = $timeEnd - $timeStart;

        $this->assertGreaterThanOrEqual(4, $timeDiff);
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

        $object->destroy();

        $this->assertEquals(2, $object->count());

        $connection1 = $object->pop();
        $connection2 = $object->pop();

        $this->assertEquals(0, $object->count());

        $this->assertEquals('y', $connection1->getResource());
        $this->assertEquals('y', $connection2->getResource());
    }
}
