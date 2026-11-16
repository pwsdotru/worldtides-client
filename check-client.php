<?php

declare(strict_types=1);

require_once(__DIR__ . "/vendor/autoload.php");

use Worldtides\API;

$errors = 0;

echo("Starting...\n");

$conf = parse_ini_file(__DIR__ . "/.env");

if ($conf === false) {
    echo("Error read .env file");
    exit(1);
}

$tides = new API($conf["apikey"]);

$tides->setDate(date("Y-m-d"))
    ->setPoint("7.8333", "98.4167");

echo("Make request for Image\n");

try {
    $result = $tides->getImage(7, ["timemode" => 24]);
    file_put_contents("test.png", $result);

    echo("Image saved to \"test.png\"\n");
} catch (Exception $e) {
    echo("\nException: " . $e->getMessage() . "\n");
    $errors++;
}

try {
    echo("Make request for Heights\n");
    $result = $tides->getHeights(7);

    print_r($result);
} catch (Exception $e) {
    echo("\nException: " . $e->getMessage() . "\n");
    $errors++;
}

try {
    echo("Make request for Min and Max\n");
    $result = $tides->getMinMax(7);

    print_r($result);
} catch (Exception $e) {
    echo("\nException: " . $e->getMessage() . "\n");
    $errors++;
}
echo("\nDone\n");
exit($errors);
