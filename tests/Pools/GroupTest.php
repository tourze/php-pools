<?php

namespace Utopia\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Utopia\Pools\Exception\PoolNotFoundException;
use Utopia\Pools\Group;
use Utopia\Pools\Pool;

/**
 * @internal
 */
#[CoversClass(Group::class)]
final class GroupTest extends TestCase
{
    protected Group $object;

    public function testAdd(): void
    {
        $this->object = new Group();
        $this->object->add(new Pool('test', 1, function () {
            return 'x';
        }));

        $this->assertInstanceOf(Pool::class, $this->object->get('test'));
    }

    public function testGet(): void
    {
        $this->object = new Group();
        $this->object->add(new Pool('test', 1, function () {
            return 'x';
        }));

        $this->assertInstanceOf(Pool::class, $this->object->get('test'));

        $this->expectException(PoolNotFoundException::class);

        $this->assertInstanceOf(Pool::class, $this->object->get('testx'));
    }

    public function testRemove(): void
    {
        $this->object = new Group();
        $this->object->add(new Pool('test', 1, function () {
            return 'x';
        }));

        $this->assertInstanceOf(Pool::class, $this->object->get('test'));

        $this->object->remove('test');

        $this->expectException(PoolNotFoundException::class);

        $this->assertInstanceOf(Pool::class, $this->object->get('test'));
    }

    public function testReclaim(): void
    {
        $this->object = new Group();
        $this->object->add(new Pool('test', 5, function () {
            return 'x';
        }));

        $this->assertEquals(5, $this->object->get('test')->count());

        $this->object->get('test')->pop();
        $this->object->get('test')->pop();
        $this->object->get('test')->pop();

        $this->assertEquals(2, $this->object->get('test')->count());

        $this->object->reclaim();

        $this->assertEquals(5, $this->object->get('test')->count());
    }

    public function testReconnectAttempts(): void
    {
        $this->object = new Group();
        $this->object->add(new Pool('test', 5, function () {
            return 'x';
        }));

        $this->assertEquals(3, $this->object->get('test')->getReconnectAttempts());

        $this->object->setReconnectAttempts(5);

        $this->assertEquals(5, $this->object->get('test')->getReconnectAttempts());
    }

    public function testReconnectSleep(): void
    {
        $this->object = new Group();
        $this->object->add(new Pool('test', 5, function () {
            return 'x';
        }));

        $this->assertEquals(1, $this->object->get('test')->getReconnectSleep());

        $this->object->setReconnectSleep(2);

        $this->assertEquals(2, $this->object->get('test')->getReconnectSleep());
    }

    public function testUse(): void
    {
        $this->object = new Group();
        $pool1 = new Pool('pool1', 1, fn () => '1');
        $pool2 = new Pool('pool2', 1, fn () => '2');
        $pool3 = new Pool('pool3', 1, fn () => '3');

        $this->object->add($pool1);
        $this->object->add($pool2);
        $this->object->add($pool3);

        $this->assertEquals(1, $pool1->count());
        $this->assertEquals(1, $pool2->count());
        $this->assertEquals(1, $pool3->count());

        // @phpstan-ignore argument.type
        $this->object->use(['pool1', 'pool3'], function ($one, $three) use ($pool1, $pool2, $pool3): void {
            $this->assertEquals('1', $one);
            $this->assertEquals('3', $three);

            $this->assertEquals(0, $pool1->count());
            $this->assertEquals(1, $pool2->count());
            $this->assertEquals(0, $pool3->count());
        });

        $this->assertEquals(1, $pool1->count());
        $this->assertEquals(1, $pool2->count());
        $this->assertEquals(1, $pool3->count());
    }
}
