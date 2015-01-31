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
use rock\events\Event;

\rock\base\Alias::setAlias('root', dirname(dirname(dirname(__DIR__))));
\rock\base\Alias::setAlias('rock', '@root/framework/core');
\rock\base\Alias::setAlias('vendor', '@root/vendor');
\rock\base\Alias::setAlias('extensions', '@root/framework/extensions');
\rock\base\Alias::setAlias('assets', '@root/public/assets');
\rock\base\Alias::setAlias('web', '/assets');
\rock\base\Alias::setAlias('webImg', '/assets/images');
\rock\base\Alias::setAlias('app', '@root/apps');
\rock\base\Alias::setAlias('common', '@app/common');
\rock\base\Alias::setAlias('frontend', '@app/frontend');
\rock\base\Alias::setAlias('backend', '@app/backend');
\rock\base\Alias::setAlias('admin', '@backend');

\rock\base\Alias::setAlias('common.ns', 'apps\\common');
\rock\base\Alias::setAlias('frontend.ns', 'apps\\frontend');
\rock\base\Alias::setAlias('backend.ns', 'apps\\backend');
\rock\base\Alias::setAlias('ns', '@common.ns');

\rock\base\Alias::setAlias('common.runtime', '@common/runtime');
\rock\base\Alias::setAlias('common.views', '@common/views');
\rock\base\Alias::setAlias('frontend.views', '@frontend/views');

\rock\base\Alias::setAlias('img', '@assets/images');
\rock\base\Alias::setAlias('images', '@img');

// regenerate CSRF token
Event::on(
    \rock\csrf\CSRF::className(),
    \rock\csrf\CSRF::EVENT_AFTER_VALID,
    function(){
        \rock\Rock::$app->csrf->get(true);
    }
);

$config =\rock\helpers\ArrayHelper::merge(
    require(\rock\base\Alias::getAlias('@rock/classes.php')),
    require(__DIR__ . '/classes.php'),
    require(__DIR__ . '/controllers.php')
);

return [
    'siteUrl'              => (new \rock\request\Request())->getHostInfo() .'/',
    'emailSender'          => $config['mail']['From'],
    'components'   => $config,
];
