<?php
/**
 * Config "frontend"
 */

$configs = require(dirname(dirname(__DIR__)) . '/common/configs/configs.php');

\rock\Rock::setAlias('scope', '@frontend');
\rock\Rock::setAlias('views', '@frontend/views');
\rock\Rock::setAlias('runtime', '@frontend/runtime');
\rock\Rock::setAlias('ns', '@frontend.ns');


$path = \rock\Rock::getAlias('@frontend') . '/configs/';

$configs['_components'] = array_merge(
    $configs['_components'],
    require($path . 'models.php'),
    require($path . 'controllers.php'),
    require($path . 'snippets.php')
);
return $configs;

