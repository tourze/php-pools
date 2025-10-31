<?php

namespace Utopia\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Utopia\Pools\Exception\PoolEmptyException;

/**
 * @internal
 */
#[CoversClass(PoolEmptyException::class)]
final class PoolEmptyExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $message = 'Test empty pool exception';
        $exception = new PoolEmptyException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
