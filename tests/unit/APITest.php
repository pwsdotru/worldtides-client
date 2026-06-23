<?php

declare(strict_types=1);

namespace unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
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


    public static function setDateProvider(): array
    {
        return [
            ["2026-05-01", "2026-05-01"],
        ];
    }

    #[DataProvider('setDateProvider')]
    public function testSetDate(string $input, string $expected): void
    {
        $obj = new API("empty");
        $obj->setDate($input);
        $params = $this->getPrivateProperty($obj, "params");
        $this->assertArrayHasKey("date", $params);
        $this->assertEquals($expected, $params["date"]);
    }

    public static function setDateErrorProvider(): array
    {
        return [
            ["222-1-2026"],
        ];
    }

    #[DataProvider('setDateErrorProvider')]
    public function testSetDateException(string $date): void
    {
        $this->expectException("Worldtides\Exception\InvalidFormatException");
        $obj = new API("empty");
        $obj->setDate($date);
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
            ["7.8333", "95 с.ш."],
            ["w34", "n45"],
        ];
    }

    #[DataProvider('setPointErrorProvider')]
    public function testSetPointException(string $lat, string $lon): void
    {
        $this->expectException("Worldtides\Exception\InvalidFormatException");
        $obj = new API("empty");
        $obj->setPoint($lat, $lon);
    }

    public function testBuildBasicUrl(): void
    {
        $obj = new API("test-key");
        $obj->setPoint("12", "15")->setDate("2026-01-01");
        $reflectionClass = new ReflectionClass($obj);
        $method = $reflectionClass->getMethod("buildBasicUrl");
        $method->setAccessible(true);
        $url = $method->invoke($obj);
        $this->assertEquals("https://www.worldtides.info/api/v3?heights&key=test-key&date=2026-01-01&lat=12.000000&lon=15.000000&days=7", $url);
    }

    public function testParseResponseSuccess(): void
    {
        $json = '{"status":200,"heights":[0,1]}';
        $obj = new API("test-key");
        $reflectionClass = new ReflectionClass($obj);
        $method = $reflectionClass->getMethod("parseResponse");
        $method->setAccessible(true);
        $data = $method->invoke($obj, $json, "heights");
        $this->assertEquals([0 => 0, 1 => 1], $data);
    }

    public function testParseResponseFail(): void
    {
        $json = '{"heights":[0,1]}';
        $obj = new API("test-key");

        $this->expectException("Worldtides\Exception\InvalidResponseException");

        $reflectionClass = new ReflectionClass($obj);
        $method = $reflectionClass->getMethod("parseResponse");
        $method->setAccessible(true);
        $method->invoke($obj, $json, "heights");
    }

    public function testGetDataFail(): void
    {
        $response = new Response(404);
        $mock = new MockHandler([$response]);
        $handler = HandlerStack::create($mock);
        $client = new Client(["handler" => $handler]);

        $obj = new API("test-key", $client);

        $this->expectException("Worldtides\Exception\InvalidResponseException");

        $reflectionClass = new ReflectionClass($obj);
        $method = $reflectionClass->getMethod("getData");
        $method->setAccessible(true);
        $method->invoke($obj, "http://test.com");
    }

    protected function getPrivateProperty(object $object, string $propertyName)
    {
        $reflectionClass = new ReflectionClass($object);
        $property = $reflectionClass->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}
