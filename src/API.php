<?php

declare(strict_types=1);

namespace Worldtides;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Worldtides\Exception\InvalidFormatException;
use Worldtides\Exception\EmptyArgumentException;
use Psr\Http\Client\ClientInterface;
use Worldtides\Exception\InvalidResponseException;

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

    /**
    * Set step size in seconds for heights.
    * @param int $step
    * @return self
    */
    public function setStep(int $step): self
    {
        $this->params["step"] = $step;
        return $this;
    }

    /**
    * Set Interval length in seconds.
    * @param int $length
    * @return self
    */
    public function setLength(int $length): self
    {
            $this->params["length"] = $length;
            return $this;
    }

    public function getHeights(int $days = 7): array
    {
        $url = $this->buildBasicUrl($days);
        return $this->makeRequest($url, "heights");
    }

    public function getImage(int $days = 7, array $params = []): string
    {
        $url = $this->buildBasicUrl($days, "heights&plot");

        foreach ($params as $key => $value) {
            $url .= sprintf("&%s=%s", (string)$key, urlencode((string)$value));
        }

        $img = $this->makeRequest($url, "plot");

        return  base64_decode($img[1]);
    }

    private function makeRequest(string $url, string $field): array
    {
        $raw = $this->getData($url);
        return $this->parseResponse($raw, $field);
    }

    private function parseResponse(string $raw, string $field): array
    {
        $data = json_decode($raw, true);

        if (!array_key_exists("status", $data) || 200 !== $data["status"]) {
            throw new InvalidResponseException("Received error status from API");
        }

        if (!array_key_exists($field, $data) || empty($data[$field])) {
            throw new InvalidResponseException(sprintf("Not found field \"%s\" in response", $field));
        }

        if ("plot" === $field) {
            $img = $data[$field];
            $pos = strpos($img, ",");

            if (false === $pos) {
                throw new InvalidResponseException("Incorrect format for \"plot\" property");
            }
            $result = explode(",", $img);
        } else {
            $result = $data[$field];
        }

        return $result;
    }

    private function getData(string $url): string
    {
        try {
            $response = $this->client->request("GET", $url, ["stream" => true]);

            $statusCode = (int)$response->getStatusCode();
        } catch (ClientException $e) {
            throw new InvalidResponseException(sprintf("Error from HTTP client: %s", $e->getMessage()));
        }
        if (200 !== $statusCode) {
            throw new InvalidResponseException(sprintf("Received error code for API: %d", $statusCode));
        }

        $body = $response->getBody();

        if (empty($body)) {
            throw new InvalidResponseException(sprintf("Empty response from API"));
        }

        $raw = "";
        while (!$body->eof()) {
            $raw .= $body->read(102400);
        }
        return $raw;
    }

    private function buildUrl(int $days = 7, string $call = "heights"): string
    {
        $url = $this->buildBasicUrl($days, $call);
        $extra = $this->buildExtraParams();
        return $url . $extra;
    }

    private function buildExtraParams(): string
    {
        $params = [
            "length" => "%d",
            "step" => "%d",
        ];
        $result = "";

        foreach ($params as $key => $placeholder) {
            if (array_key_exists($key, $this->params) && !empty($this->params[$key])) {
                $result  .=  sprintf("&%s=" . $placeholder, $key, $this->params[$key]);
            }
        }
        return $result;
    }

    private function buildBasicUrl(int $days = 7, string $call = "heights"): string
    {
        if (empty($this->params["date"])) {
            throw new EmptyArgumentException("You should set date for request");
        }

        if (empty($this->params["lat"]) || empty($this->params["lon"])) {
            throw new EmptyArgumentException(("You should set coordinates for request"));
        }

        return sprintf(
            "%s?%s&key=%s&date=%s&lat=%.06f&lon=%.06f&days=%d",
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
