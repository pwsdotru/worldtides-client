<?php

declare(strict_types=1);

require_once(__DIR__ . "/vendor/autoload.php");

use Worldtides\API;

$conf = parse_ini_file(__DIR__ . "/.env");

$tides = new API($conf["apikey"]);

$result = $tides->setDate(date("Y-m-d"))
    ->setPoint("7.8333", "98.4167")
    ->getImage();

$pos = strpos($result, ",");
if (false !== $pos) {
    file_put_contents("test.png", base64_decode(substr($result, $pos + 1)));
}
