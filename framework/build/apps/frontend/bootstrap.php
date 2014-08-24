<?php
require(dirname(dirname(__DIR__)) . '/vendor/autoload.php');


if (!$configs = require(__DIR__ . '/configs/configs.php')) {
    die('configs is empty/not found');
}

require(dirname(dirname(__DIR__)) . '/framework/bootstrap.php');