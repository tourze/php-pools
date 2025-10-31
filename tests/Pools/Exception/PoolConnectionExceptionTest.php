<?php

namespace Utopia\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Utopia\Pools\Exception\PoolConnectionException;

/**
 * @internal
 */
#[CoversClass(PoolConnectionException::class)]
final class PoolConnectionExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $message = 'Test connection exception';
        $exception = new PoolConnectionException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
