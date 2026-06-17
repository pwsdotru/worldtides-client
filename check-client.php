<?php

declare(strict_types=1);

require_once(__DIR__ . "/vendor/autoload.php");

use Worldtides\API;

echo("Starting...\n");

$conf = parse_ini_file(__DIR__ . "/.env");

$tides = new API($conf["apikey"]);

$tides->setDate(date("Y-m-d"))
    ->setPoint("7.8333", "98.4167");

echo("Make request for Image\n");

$result = $tides->getImage(7, ["timemode" => 24]);
file_put_contents("test.png", $result);

echo("Image saved to \"test.png\"\n");

echo("Make request for Heights\n");
$result = $tides->getHeights(7);

print_r($result);

echo("\nDone\n");
