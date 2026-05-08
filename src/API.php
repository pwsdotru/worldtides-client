<?php

declare(strict_types=1);

namespace Worldtides;

use GuzzleHttp\Client;
use Worldtides\Exception\InvalidFormatException;
use Psr\Http\Client\ClientInterface;

class API
{
    private const ENDPOINT = "https://www.worldtides.info/api/v3";

    private ?ClientInterface $client = null;
    private array $params = [];

    public function __construct(protected readonly string $apikey, ?ClientInterface $client = null)
    {
        if (null === $client) {
            $this->client = new Client();
        } else {
            $this->client = $client;
        }
    }

    public function setDate(string $date): self
    {
        $this->params["date"] = $date;
        return $this;
    }

    /**
    * Set geo coordinates
    * @param string $lat
    * @param string $lon
    * @return self
    * @throws InvalidFormatException
    */
    public function setPoint(string $lat, string $lon): self
    {
        if (is_numeric($lat) && is_numeric($lon)) {
            $this->params["lat"] = (float)$lat;
            $this->params["lon"] = (float)$lon;
        } else {
            throw new InvalidFormatException(sprintf("Incorrect format for point coordinates (%s, %s)", $lat, $lon));
        }
        return $this;
    }

    public function getHeights(int $days = 7): array
    {
        $result = [];
        $url = sprintf(
            "%s?heights&key=%s&date=%s&lat=%.06f&lon=%.06f&days=%d",
            self::ENDPOINT,
            $this->apikey,
            $this->params["date"],
            $this->params["lat"],
            $this->params["lon"],
            $days
        );

        echo($url);
        $response = $this->client->request("GET", $url);
        echo($response->getBody());
        return $result;
    }
}
