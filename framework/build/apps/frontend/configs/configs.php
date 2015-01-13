<?php
/**
 * Config "frontend"
 */

$config = require(dirname(dirname(__DIR__)) . '/common/configs/configs.php');

\rock\Rock::setAlias('scope', '@frontend');
\rock\Rock::setAlias('views', '@frontend/views');
\rock\Rock::setAlias('runtime', '@frontend/runtime');
\rock\Rock::setAlias('ns', '@frontend.ns');


$path = \rock\Rock::getAlias('@frontend') . '/configs/';

$config['components'] = \rock\helpers\ArrayHelper::merge(
    $config['components'],
    require(__DIR__ . '/models.php'),
    require(__DIR__ . '/controllers.php'),
    require(__DIR__ . '/snippets.php')
);
return $configs;

