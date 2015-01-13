<?php
/**
 * Common config
 *
 * @rock - framework directory.
 * @app - base path of currently running application.
 * @runtime - runtime of currently directory.
 * @vendor - Composer vendor directory.
 * @scope - scope root directory of currently running web application.
 * @web - base URL of currently running web application.
 */
use rock\base\Config;
use rock\helpers\String;
use rock\Rock;
use rock\template\Template;

\rock\Rock::setAlias('root', dirname(dirname(dirname(__DIR__))));
\rock\Rock::setAlias('vendor', '@root/vendor');
\rock\Rock::setAlias('extensions', '@root/framework/extensions');
\rock\Rock::setAlias('assets', '@root/public/assets');
\rock\Rock::setAlias('web', '/assets');
\rock\Rock::setAlias('webImg', '/assets/images');
\rock\Rock::setAlias('app', '@root/apps');
\rock\Rock::setAlias('common', '@app/common');
\rock\Rock::setAlias('frontend', '@app/frontend');
\rock\Rock::setAlias('backend', '@app/backend');
\rock\Rock::setAlias('admin', '@backend');

\rock\Rock::setAlias('common.ns', 'apps\\common');
\rock\Rock::setAlias('frontend.ns', 'apps\\frontend');
\rock\Rock::setAlias('backend.ns', 'apps\\backend');
\rock\Rock::setAlias('ns', '@common.ns');

\rock\Rock::setAlias('common.runtime', '@common/runtime');
\rock\Rock::setAlias('common.views', '@common/views');
\rock\Rock::setAlias('frontend.views', '@frontend/views');

\rock\Rock::setAlias('img', '@assets/images');
\rock\Rock::setAlias('images', '@img');

$config = \rock\helpers\ArrayHelper::merge(
    require(\rock\Rock::getAlias('@rock/classes.php')),
    require(__DIR__ . '/classes.php'),
    require(__DIR__ . '/controllers.php'),
    require(__DIR__ . '/snippets.php')
);

return [
    'siteUrl'       => (new \rock\request\Request())->getHostInfo() .'/',
    'emailSender'   => $config['mail']['From'],
    'components'    => $config,
];
