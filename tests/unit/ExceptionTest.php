<?php

declare(strict_types=1);

namespace unit;

use Worldtides\Exception;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function testContructDefault(): void
    {
        $this->expectException("Worldtides\Exception");
        throw new Exception("test");
    }
}
