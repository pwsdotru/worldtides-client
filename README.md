# PHP Client for [WorldTides.com](https://www.worldtides.com/)

API docs: https://www.worldtides.info/apidocs


## Install

Via composer

````
composer require pwsdotru/worldtides-client
````

## Usage

````php
use Worldtides\API;

$tides = new API('apikey');

$tides->setDate(date("Y-m-d"))
    ->setPoint("7.8333", "98.4167");

$heights = $tides->$tides->getHeights(7);
````

In **$heights** have array of points for next 7 days.