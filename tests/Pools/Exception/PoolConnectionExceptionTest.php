<?php

namespace Utopia\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Utopia\Pools\Exception\PoolConnectionException;

class PoolConnectionExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $message = 'Test connection exception';
        $exception = new PoolConnectionException($message);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}