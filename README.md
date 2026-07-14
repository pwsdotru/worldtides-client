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

Array contains sets of array

````
Array
(
    [0] => Array
        (
            [dt] => 1782752400
            [date] => 2026-06-29T17:00+0000
            [height] => 0.553
        )

    [1] => Array
        (
            [dt] => 1782754200
            [date] => 2026-06-29T17:30+0000
            [height] => 0.422
        )
....
````
Where **dt** and **date** are time (in unix timestamp and string format). 
**height** is height of water in this time. 


````php
use Worldtides\API;

$tides = new API('apikey');

$tides->setDate(date("Y-m-d"))
    ->setPoint("7.8333", "98.4167");

$img = $tides->$tides->getImage(7);
````
In **$img** have binary data of PNG image with graph for 7 days.
