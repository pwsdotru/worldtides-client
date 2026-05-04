<?php

declare(strict_types=1);

require_once(__DIR__ . "/vendor/autoload.php");

use Worldtides\API;

$conf = parse_ini_file(__DIR__ . "/.env");

$tides = new API($conf["apikey"]);

$tides->setDate(date("Y-m-d"))
    ->setPoint("7.8900", " 98.3983");
