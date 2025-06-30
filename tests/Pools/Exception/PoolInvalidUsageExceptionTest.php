<?php

namespace Utopia\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Utopia\Pools\Exception\PoolInvalidUsageException;

class PoolInvalidUsageExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $message = 'Test invalid usage exception';
        $exception = new PoolInvalidUsageException($message);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}