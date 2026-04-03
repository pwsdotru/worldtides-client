<?php

declare(strict_types=1);

namespace Tests;

use Worldtides\API;
use ReflectionClass;
use PHPUnit\Framework\TestCase;

final class APITest extends TestCase
{
    public static function testContruct(): void
    {
        $obj = new API("test-key");
        self::assertEquals("test-key", self::getPrivateProperty($obj, "apikey"));
    }

    protected static function getPrivateProperty(object $object, string $propertyName)
    {
        $reflectionClass = new ReflectionClass($object);
        $property = $reflectionClass->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}
