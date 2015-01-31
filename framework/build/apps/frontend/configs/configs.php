<?php

// Config "frontend"
$config = require(dirname(dirname(__DIR__)) . '/common/configs/configs.php');

\rock\base\Alias::setAlias('scope', '@frontend');
\rock\base\Alias::setAlias('views', '@frontend/views');
\rock\base\Alias::setAlias('runtime', '@frontend/runtime');
\rock\base\Alias::setAlias('ns', '@frontend.ns');


$path = \rock\base\Alias::getAlias('@frontend') . '/configs/';

$config['components'] = \rock\helpers\ArrayHelper::merge(
    $config['components'],
    require(__DIR__ . '/models.php'),
    require(__DIR__ . '/controllers.php')
);
return $configs;

