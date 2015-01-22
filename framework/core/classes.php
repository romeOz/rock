<?php

/**
 * Container "Models"
 */
use apps\common\rbac\UserRole;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Cache\Adapter;
use rock\db\BatchQueryResult;
use rock\file\FileManager;
use rock\helpers\ArrayHelper;
use rock\i18n\i18n;
use rock\rbac\Permission;
use rock\rbac\Role;
use rock\Rock;
use rock\security\Security;
use rock\template\Template;

return array_merge(
    [
        'access' => [
            'class' => \rock\access\Access::className(),
        ],

        // Database
        'db' => [
            'class' => \rock\db\Connection::className(),
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8',
            'dsn' => 'mysql:host=localhost;dbname=rockdemo;charset=utf8',
            'tablePrefix' => 'spt_',
            'aliasSeparator' => '__',
        ],
        'BatchQueryResult' => [
            'class' => BatchQueryResult::className(),
        ],
        'mongodb' => [
            'class' => \rock\mongodb\Connection::className(),
            'dsn' => 'mongodb://developer:password@localhost:27017/mydatabase',
        ],
        'cache' => [
            'class' => \rock\cache\CacheStub::className(),
        ],
//        'cache' => [
//            'class' => \rock\cache\CacheFile::className(),
//            'adapter' => function () {
//                    return Rock::factory(
//                        [
//                            'class' => FileManager::className(),
//                            'adapter' =>
//                                function () {
//                                    return new Local(Rock::getAlias('@common/runtime/cache'));
//                                },
//                            'config' => ['visibility' => FileManager::VISIBILITY_PRIVATE],
//                            'cache' => function () {
//                                    $local = new Local(Rock::getAlias('@common/runtime/filesystem'));
//                                    $cache = new Adapter($local, 'cache.tmp');
//
//                                    return $cache;
//                                }
//                        ]
//                    );
//                }
//        ],
        'template' => [
            'class' => Template::className(),
            'locale' => function(){
                return Rock::$app->language;
            },
            'autoEscape' => Template::ESCAPE | Template::TO_TYPE,
            'handlerLink' => function($link, Template $template)
                {
                    $class = $link[0];
                    if (!class_exists($class)) {
                        /** @var \rock\base\Controller $class */
                        if (!$class = ArrayHelper::getValue((array)\rock\di\Container::get($class), ['class'])) {
                            throw new \rock\template\TemplateException(\rock\template\TemplateException::UNKNOWN_CLASS, ['class' => $link[0]]);
                        }
                    }

                    //  Get url by context
                    if (count($link) === 1) {
                        $urlBuilder = \rock\url\Url::set($class::context(['url']));
                        return $template->autoEscape($urlBuilder->get());
                    }
                    // Get url by resource
                    if (count($link) > 1) {
                        $urlBuilder = \rock\url\Url::set($class::findUrlById($link[1]));
                        return $template->autoEscape($urlBuilder->get());
                    }
                    return '#';
                },
            'filters' => [
                'size' => [
                    'class' => \rock\template\filters\StringFilter::className(),
                ],
                'trimPattern' => [
                    'class' => \rock\template\filters\StringFilter::className(),
                ],
                'contains' => [
                    'class' => \rock\template\filters\StringFilter::className(),
                ],
                'truncate' => [
                    'class' => \rock\template\filters\StringFilter::className(),
                ],
                'truncateWords' => [
                    'class' => \rock\template\filters\StringFilter::className(),
                ],
                'upper' => [
                    'class' => \rock\template\filters\StringFilter::className(),
                ],
                'lower' => [
                    'class' => \rock\template\filters\StringFilter::className(),
                ],
                'upperFirst' => [
                    'class' => \rock\template\filters\StringFilter::className(),
                ],
                'encode' => [
                    'class' => \rock\template\filters\StringFilter::className(),
                ],
                'decode' => [
                    'class' => \rock\template\filters\StringFilter::className(),
                ],
                'markdown' => [
                    'class' => \rock\template\filters\StringFilter::className(),
                ],
                'paragraph' => [
                    'class' => \rock\template\filters\StringFilter::className(),
                ],
                'isParity' => [
                    'class' => \rock\template\filters\NumericFilter::className(),
                ],
                'positive' => [
                    'class' => \rock\template\filters\NumericFilter::className(),
                ],
                'formula' => [
                    'class' => \rock\template\filters\NumericFilter::className(),
                ],
                'unserialize' => [
                    'class' => \rock\template\filters\BaseFilter::className(),
                ],
                'replaceTpl' => [
                    'class' => \rock\template\filters\BaseFilter::className(),
                ],
                'modifyDate' => [
                    'class' => \rock\template\filters\BaseFilter::className(),
                ],
                'date' => [
                    'class' => \rock\template\filters\BaseFilter::className(),
                ],
                'modifyUrl' => [
                    'class' => \rock\template\filters\BaseFilter::className(),
                ],
                'url' => [
                    'method' => 'modifyUrl',
                    'class' => \rock\template\filters\BaseFilter::className(),
                ],
                'arrayToJson' => [
                    'class' => \rock\template\filters\BaseFilter::className(),
                ],
                'toJson' => [
                    'method' => 'arrayToJson',
                    'class' => \rock\template\filters\BaseFilter::className(),
                ],
                'jsonToArray' => [
                    'method' => 'unserialize',
                    'class' => \rock\template\filters\BaseFilter::className(),
                ],
                'toArray' => [
                    'method' => 'unserialize',
                    'class' => \rock\template\filters\BaseFilter::className(),
                ],
                'notEmpty' => [
                    'class' => \rock\template\filters\ConditionFilter::className(),
                ],
                'empty' => [
                    'method' => '_empty',
                    'class' => \rock\template\filters\ConditionFilter::className(),

                ],
                'if' => [
                    'method' => '_if',
                    'class' => \rock\template\filters\ConditionFilter::className(),
                ],
                'thumb' => [
                    'class' => \rock\template\filters\BaseFilter::className(),
                ],
            ],
            'extensions' => [
                'user' => function (array $keys) {
                        if (current($keys) === 'isGuest') {
                            return Rock::$app->user->isGuest();
                        } elseif (in_array(current($keys), ['isLogged', 'isAuthenticated'], true)) {
                            return !Rock::$app->user->isGuest();
                        }
                        return \rock\helpers\ArrayHelper::getValue(Rock::$app->user->getAll(), $keys);
                    },
                'call' => function (array $call, array $params = [], Template $template) {
                        if (!isset($call[1])) {
                            $call[1] = null;
                        }
                        list($class, $method) = $call;
                        if ($class === 'context') {
                            $object = $template->context;
                            $function = [$object, $method];
                        } elseif (function_exists($class) && !$class instanceof \Closure){
                            return call_user_func_array($class, $params);
                        } else {
                            $object = Rock::factory(Rock::getAlias($class));
                            if (!method_exists($object, $method)) {
                                throw new \rock\exception\BaseException(\rock\exception\BaseException::UNKNOWN_METHOD, ['method' => "{$class}::{$method}"]);
                            }
                            $function = [$object, $method];
                        }

                        return call_user_func_array($function, $params);
                    },
            ],
            'title' => 'Demo',
            'metaTags' => function(){
                    return [
                        '<meta charset="'.Rock::$app->charset.'" />',
                    ];
                },
            'linkTags' => [
                '<link rel="Shortcut Icon" type="image/x-icon" href="/favicon.ico?10">',
            ],
        ],

        'execute' => [
            'class' => \rock\execute\CacheExecute::className(),
        ],

        'i18n' => [
            'class' => \rock\i18n\i18n::className(),
            'pathsDicts' => [
                i18n::RU => [
                    '@common/lang/ru/lang.php',
                    '@common/lang/ru/validate.php',
                ],
                i18n::EN => [
                    '@common/lang/en/lang.php',
                    '@common/lang/en/validate.php',
                ]
            ],
            'locale' => function(){
                return Rock::$app->language;
            }
        ],
        'date' => [
            'class' => \rock\date\DateTime::className(),
            'locale' => function(){
                return Rock::$app->language;
            },
            'formats' => [
                'dmy'   => function(\rock\date\DateTime $dateTime){
                        $nowYear  = date('Y');
                        $lastYear = $dateTime->format('Y');

                        return $nowYear > $lastYear
                            ? $dateTime->format('j F Y')
                            : $dateTime->format('d F');
                    },
                'dmyhm' => function(\rock\date\DateTime $dateTime){
                        $nowYear  = date('Y');
                        $lastYear = $dateTime->format('Y');
                        return $nowYear > $lastYear
                            ? $dateTime->format('j F Y H:i')
                            : $dateTime->format('j F H:i');
                    },
            ]
        ],

        'mail' => [
            'class' => \rock\mail\Mail::className(),
            'From' => 'support@' . (new \rock\request\Request())->getHost(),
            'FromName' => 'Rock Framework',
        ],
        'request' => [
            'class' => \rock\request\Request::className(),
        ],
        'requestCollection' => [
            'class' => \rock\request\RequestCollection::className(),
        ],
        'response' => [
            'class' => \rock\response\Response::className(),
            'singleton' => true,
            'locale' => function(){
                return Rock::$app->language;
            }
        ],
        'htmlResponseFormatter' => [
            'class' => \rock\response\HtmlResponseFormatter::className(),
            //'singleton' => true,
        ],
        'jsonResponseFormatter' => [
            'class' => \rock\response\JsonResponseFormatter::className(),
            //'singleton' => true,
        ],
        'xmlResponseFormatter' => [
            'class' => \rock\response\XmlResponseFormatter::className(),
            //'singleton' => true,
        ],
        'rssResponseFormatter' => [
            'class' => \rock\response\RssResponseFormatter::className(),
            //'singleton' => true,
        ],

        'route' => [
            'class' => \rock\route\Route::className(),
        ],

        'sphinx' => [
            'class' => \rock\sphinx\Connection::className(),
            'dsn' => 'mysql:host=127.0.0.1;port=9306;charset=utf8;',
            'username' => '',
            'password' => '',
        ],
        'session' => [
            'class' => \rock\session\Session::className(),
            //'singleton' => true,
            'cookieParams' => [
                'httponly' => true,
                'lifetime' => 60 * 60 * 24 * 60,
                'setUseCookies' => \rock\session\Session::USE_ONLY_COOKIES
            ],
        ],
        'cookie' => [
            'class' => \rock\cookie\Cookie::className(),
            //'singleton' => true,
        ],

        'dataImage' => [
            'class' => \rock\image\DataProvider::className(),
            'adapterImage' => function () {
                    return Rock::factory(
                        [
                            'class' => FileManager::className(),
                            'adapter' =>
                                function () {
                                    return new Local(Rock::getAlias('@assets/images'));
                                },
                            'cache' => function () {
                                    $local = new Local(Rock::getAlias('@common.runtime/filesystem'));
                                    $cache = new Adapter($local, 'images.tmp');

                                    return $cache;
                                }
                        ]
                    );
                },
            'adapterCache' => function () {
                    return Rock::factory(
                        [
                            'class' => FileManager::className(),
                            'adapter' =>
                                function () {
                                    return new Local(Rock::getAlias('@assets/cache'));
                                },
                            'cache' => function () {
                                    $local = new Local(Rock::getAlias('@common.runtime/filesystem'));
                                    $cache = new Adapter($local, 'image_cache.tmp');

                                    return $cache;
                                }
                        ]
                    );
                },
        ],
        'csrf' => [
            'class' => \rock\csrf\CSRF::className(),
        ],
        'url' => [
            'class' => \rock\url\Url::className(),
        ],
        'validate' => [
            'class' => \rock\validate\Validate::className(),
            'locale' => function (){
                return Rock::$app->language;
            }
        ],
        \rock\validate\ValidateModel::className() => [
            'class' => \rock\validate\ValidateModel::className(),
            'locale' => function (){
                return Rock::$app->language;
            }
        ],
        'captcha' => [
            'class' => \rock\captcha\Captcha::className(),
            /**
             * Captcha string length
             */
            'length' => 0,
            /**
             * Noise white
             */
            'whiteNoiseDensity' => 1 / 6,
            /**
             * Noise black
             */
            'blackNoiseDensity' => 1 / 30,
        ],
        'file' => [
            'class' => \rock\file\FileManager::className(),
        ],
        'user' => [
            'class' => \rock\user\User::className(),
            'container' => 'user',
        ],

        'activeData' => [
            'class' => \rock\db\ActiveDataProvider::className(),
        ],
        'di' => [
            'class' => \rock\di\Container::className(),
        ],
        'log' => [
            'class' => \rock\log\Log::className(),
        ],

        'behavior' => [
            'class' => \rock\base\Behavior::className(),
        ],
        \rock\filters\AccessFilter::className() => [
            'class' => \rock\filters\AccessFilter::className(),
        ],
        'rbac' =>[
            'class' => \rock\rbac\DBManager::className(),
        ],
        'markdown' =>[
            'class' => \rock\markdown\Markdown::className(),
            'handlerLinkByUsername' => function($username){
                    return \apps\common\models\users\Users::findUrlByUsername($username);
                }
        ],
        'uploadedFile' =>[
            'class' => \rock\file\UploadedFile::className(),
            'adapter' => function () {
                    return Rock::factory(
                        [
                            'class' => FileManager::className(),
                            'adapter' =>
                                function () {
                                    return new Local(Rock::getAlias('@assets/images'));
                                },
                            'cache' => function () {
                                    $local = new Local(Rock::getAlias('@common.runtime/filesystem'));
                                    $cache = new Adapter($local, 'images.tmp');

                                    return $cache;
                                }
                        ]
                    );
                },
            'calculatePathname' => function(\rock\file\UploadedFile $upload, $path, FileManager $fileManager = null) {
                    $pathname = !empty($path) ? [$path] : [];

                    if (isset($fileManager)) {
                        $num = floor(
                            count(
                                $fileManager
                                    ->listContents(
                                        "~/^\\d+\//",
                                        true,
                                        FileManager::TYPE_FILE
                                    )
                            ) / 500);

                        if (isset($num)) {
                            $pathname[] =$num;
                        }
                    }

                    $pathname[] = str_shuffle(md5_file($upload->tempName));
                    return implode(DS, $pathname) . ".{$upload->extension}";
                }
        ],
        'security' => [
            'class' => Security::className(),
        ],
        'sanitize' => [
            'class' => \rock\sanitize\Sanitize::className(),
        ],
        Role::className() =>[
            'class' => Role::className(),
        ],
        Permission::className() =>[
            'class' => Permission::className(),
        ],
        UserRole::className() =>[
            'class' => UserRole::className(),
        ],
    ],
    require(__DIR__ . '/widgets.php'),
    require(__DIR__ . '/snippets.php')
);
