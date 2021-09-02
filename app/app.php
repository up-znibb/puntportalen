<?php

session_start();

use SITE\App;

$container = require_once CONTAINER;

// Starta
$app = new App($container);
$app->run();
