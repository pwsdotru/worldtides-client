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

    public static function setPointProvider(): array
    {
        return [
            ["7.8333", "98.4167", 7.8333, 98.4167],
        ];
    }

    #[DataProvider('setPointProvider')]
    public function testSetPoint(string $lat, string $lon, float $expected_lat, float $expected_lon): void
    {
        $obj = new API("empty");
        $obj->setPoint($lat, $lon);
        $params = $this->getPrivateProperty($obj, "params");
        $this->assertArrayHasKey("lat", $params);
        $this->assertArrayHasKey("lon", $params);
        $this->assertEqualsWithDelta($expected_lat, $params["lat"], 0.01);
        $this->assertEqualsWithDelta($expected_lon, $params["lon"], 0.01);
    }

    public static function setPointErrorProvider(): array
    {
        return [
            ["7.8333", "erorr"],
            ["test", "95"],
            ["7.8333", "95 ñ.ø."],
            ["w34", "n45"],
        ];
    }

    #[DataProvider('setPointErrorProvider')]
    public function testSetPointException(string $lat, string $lon): void
    {
        $this->expectException("Worldtides\Exception");
        $obj = new API("empty");
        $obj->setPoint($lat, $lon);
    }

    protected function getPrivateProperty(object $object, string $propertyName)
    {
        $reflectionClass = new ReflectionClass($object);
        $property = $reflectionClass->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}
