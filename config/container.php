
<?php

use Pimple\Container;
use SITE\Tools\PDO;
use SITE\Model\User;

$container = new Container();

$container['devmode'] = false;

// PDO
$container['PDO'] = function ($c) {
    // return new PDO();
    return new PDO($c['devmode']);
};
return $container;