<?php

declare(strict_types=1);

namespace unit;

use Worldtides\API;
use ReflectionClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class APITest extends TestCase
{
    public function testConstruct(): void
    {
        $obj = new API("test-key");
        $this->assertEquals("test-key", $this->getPrivateProperty($obj, "apikey"));
    }


    public static function setDataProvider(): array
    {
        return [
            ["2026-05-01", "2026-05-01"],
        ];
    }

    #[DataProvider('setDataProvider')]
    public function testSetDate(string $input, string $expected): void
    {
        $obj = new API("empty");
        $obj->setDate($input);
        $params = $this->getPrivateProperty($obj, "params");
        $this->assertArrayHasKey("date", $params);
        $this->assertEquals($expected, $params["date"]);
    }
    protected function getPrivateProperty(object $object, string $propertyName)
    {
        $reflectionClass = new ReflectionClass($object);
        $property = $reflectionClass->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}
