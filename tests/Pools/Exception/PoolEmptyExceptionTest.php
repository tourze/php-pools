<?php

namespace Utopia\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Utopia\Pools\Exception\PoolEmptyException;

class PoolEmptyExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $message = 'Test empty pool exception';
        $exception = new PoolEmptyException($message);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}