<?php

declare(strict_types=1);

namespace Worldtides;

class API
{
    private string $endpoint = "https://www.worldtides.info/api/v3";

    private array $params = [];

    public function __construct(protected readonly string $apikey)
    {
    }

    public function setDate(string $date): self
    {
        $this->params["date"] = $date;
        return $this;
    }

    public function setPoint(string $lat, string $lon): self
    {
        $this->params["lat"] = $lat;
        $this->params["lon"] = $lon;
        return $this;
    }
}
