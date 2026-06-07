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

    /**
    * Set date for API call
    * @param string $date
    * @return self
    * @throws InvalidFormatException
    */
    public function setDate(string $date): self
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            throw new InvalidFormatException(sprintf("Incorrect format for date (%s)", $date));
        }
        $this->params["date"] = date('Y-m-d', $timestamp);
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
        $url = $this->buildBasicUrl($days);
        $response = $this->client->request("GET", $url);
        echo($response->getBody());
        return $result;
    }

    public function getImage(int $days = 7, array $params = []): string
    {
        $url = $this->buildBasicUrl($days, "heights&plot");
        $response = $this->client->request("GET", $url, ["stream" => true]);
        $body = $response->getBody();
        $raw = "";
        while (!$body->eof()) {
            $raw .= $body->read(102400);
        }
        $data = json_decode($raw, true);
        return $data["plot"];
    }

    private function buildBasicUrl(int $days = 7, string $call = "heights"): string
	{
		return sprintf("%s?%s&key=%s&date=%s&lat=%.06f&lon=%.06f&days=%d",
			self::ENDPOINT,
			$call,
			$this->apikey,
			$this->params["date"],
			$this->params["lat"],
			$this->params["lon"],
			$days
		);
	}
}
