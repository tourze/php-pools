<?php

namespace Utopia\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Utopia\Pools\Exception\PoolNotFoundException;

class PoolNotFoundExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $message = 'Test pool not found exception';
        $exception = new PoolNotFoundException($message);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}