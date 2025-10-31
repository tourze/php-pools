<?php

namespace Utopia\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Utopia\Pools\Exception\PoolInvalidUsageException;

/**
 * @internal
 */
#[CoversClass(PoolInvalidUsageException::class)]
final class PoolInvalidUsageExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $message = 'Test invalid usage exception';
        $exception = new PoolInvalidUsageException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
