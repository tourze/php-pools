<?php

namespace Utopia\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Utopia\Pools\Exception\PoolNotFoundException;

/**
 * @internal
 */
#[CoversClass(PoolNotFoundException::class)]
final class PoolNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $message = 'Test pool not found exception';
        $exception = new PoolNotFoundException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
