<?php

declare(strict_types=1);

namespace unit\Exception;

use Worldtides\API;
use Worldtides\Exception\EmptyArgumentException;
use PHPUnit\Framework\TestCase;

class EmptyArgumentExceptionTest extends TestCase
{
    public function testException(): void
    {
        $obj = new API("test");
        $this->expectException("Worldtides\Exception\EmptyArgumentException");
        $obj->getHeights();
    }
}
