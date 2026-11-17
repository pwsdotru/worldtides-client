<?php

declare(strict_types=1);

namespace Worldtides;

use Psr\Http\Client\ClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use Worldtides\Exception;
use Worldtides\Exception\InvalidFormatException;
use Worldtides\Exception\EmptyArgumentException;
use Worldtides\Exception\InvalidResponseException;

class API
{
    private const ENDPOINT = "https://www.worldtides.info/api/v3";

    private ?ClientInterface $client = null;
    /** @var array <string, mixed> */
    private array $params = [];
    /** @var array <int, array<string, mixed>> */
    private array $heights = [];


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

    /**
    * @param int $days
    * @return array <int, mixed>
    */
    public function getHeights(int $days = 7): array
    {
        if (empty($this->heights)) {
            $url = $this->buildUrl($days);
            return $this->makeRequest($url);
        }
        return $this->heights;
    }

    /**
    * @param int $days
    * @param array<string, mixed> $params
    * @return string
    */
    public function getImage(int $days = 7, array $params = []): string
    {
        $url = $this->buildUrl($days, "heights&plot");

        foreach ($params as $key => $value) {
            $url .= sprintf("&%s=%s", (string)$key, urlencode((string)$value));
        }

        $img = $this->makeRequest($url, "plot");

        return  base64_decode($img[1]);
    }

    /**
    * @param int $days
    * @return array <string, array<int, mixed>>
    */
    public function getMinMax(int $days = 7): array
    {
        if (empty($this->heights)) {
            $this->getHeights($days);
        }
        return $this->calculateExtremum();
    }
    /**
    * @param string $url
    * @param string $field
    * @return array <int, mixed>
    */
    private function makeRequest(string $url, string $field = "heights"): array
    {
        $raw = $this->getData($url);
        return $this->parseResponse($raw, $field);
    }

    /**
    * @param string $raw
    * @param string $field
    * @return array <int, mixed>
    * @throws InvalidResponseException
    */
    private function parseResponse(string $raw, string $field): array
    {
        $data = json_decode($raw, true);

        if (!array_key_exists("status", $data) || 200 !== $data["status"]) {
            throw new InvalidResponseException("Received error status from API");
        }

        if (!array_key_exists($field, $data) || empty($data[$field])) {
            throw new InvalidResponseException(sprintf("Not found field \"%s\" in response", $field));
        }

        if (array_key_exists("heights", $data) && !empty($data["heights"])) {
            $this->heights = $data["heights"];
        }

        if ("plot" === $field) {
            $img = $data[$field];
            $pos = strpos($img, ",");

            if (false === $pos) {
                throw new InvalidResponseException("Incorrect format for \"plot\" property");
            }
            $result = explode(",", $img);
        } else {
            $result = $this->heights;
        }

        return $result;
    }

    private function getData(string $url): string
    {
        if ($this->client === null) {
            throw new Exception("HTTP Client Error");
        }
        try {
            $request = new Request("GET", $url, ["stream" => "true"]);
            $response = $this->client->sendRequest($request);

            $statusCode = (int)$response->getStatusCode();
        } catch (ClientException $e) {
            throw new InvalidResponseException(sprintf("Error from HTTP client: %s", $e->getMessage()));
        }
        if (200 !== $statusCode) {
            throw new InvalidResponseException(sprintf("Received error code for API: %d", $statusCode));
        }

        $body = $response->getBody();

        $raw = "";
        while (!$body->eof()) {
            $raw .= $body->read(102400);
        }

        if ($raw === "") {
            throw new InvalidResponseException(sprintf("Empty response from API"));
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

    /**
    * @return array <string, array<int, mixed>>
    **/
    private function calculateExtremum(): array
    {
        $key = "height";
        $result = ["min" => [], "max" => []];
        $cnt = count($this->heights) - 1;
        for ($i = 1; $i < $cnt; $i++) {
            if ($this->heights[$i - 1][$key] < $this->heights[$i][$key] && $this->heights[$i + 1][$key] < $this->heights[$i][$key]) {
                $result["max"][] = $this->heights[$i];
            }
            if ($this->heights[$i - 1][$key] > $this->heights[$i][$key] && $this->heights[$i + 1][$key] > $this->heights[$i][$key]) {
                $result["min"][] = $this->heights[$i];
            }
        }
        return $result;
    }
}
