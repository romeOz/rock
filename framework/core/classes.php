<?php
use apps\common\rbac\UserRole;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Cached\Storage\Adapter;
use rock\base\Alias;
use rock\db\BatchQueryResult;
use rock\file\FileManager;
use rock\helpers\ArrayHelper;
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
//                    return \rock\di\Container::load(
//                        [
//                            'class' => FileManager::className(),
//                            'adapter' => new Local(Alias::getAlias('@common/runtime/cache')),
//                            'config' => ['visibility' => FileManager::VISIBILITY_PRIVATE],
//                            'cache' => new Adapter(new Local(Alias::getAlias('@common/runtime/filesystem')), 'cache.tmp')
//                        ]
//                    );
//                }
//        ],
        'template' => [
            'class' => Template::className(),
            'locale' => Rock::$app->language,
            'autoEscape' => Template::ESCAPE | Template::TO_TYPE,
            'handlerLink' => function($link, Template $template)
                {
                    $class = $link[0];
                    if (!class_exists($class)) {
                        /** @var \rock\core\Controller $class */
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

            'extensions' => [
                'cfg' => function (array $keys) {
                    return \rock\helpers\ArrayHelper::getValue(Rock::$config, $keys);
                },
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
                            $object = \rock\di\Container::load(Alias::getAlias($class));
                            if (!method_exists($object, $method)) {
                                throw new \rock\base\BaseException(\rock\base\BaseException::UNKNOWN_METHOD, ['method' => "{$class}::{$method}"]);
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
            'snippets' => [
                'ListView' => [
                    'class'        => \rock\snippets\ListView::className(),
                ],

                'Date' => [
                    'class'        => \rock\snippets\Date::className(),
                ],

                'For' => [
                    'class'        => \rock\snippets\ForSnippet::className(),
                ],

                'Formula' => [
                    'class'        => \rock\snippets\Formula::className(),
                ],

                'If' => [
                    'class'        => \rock\snippets\IfSnippet::className(),
                ],

                'Pagination' => [
                    'class'        => \rock\snippets\Pagination::className(),
                ],

                'request\Get' => [

                    'class'        => \rock\snippets\request\Get::className(),
                ],

                'request\Post' => [
                    'class'        => \rock\snippets\request\Post::className(),
                ],

                'CSRF' => [
                    'class'        => \rock\snippets\CSRF::className(),
                ],

                'Url' => [
                    'class'        => \rock\snippets\Url::className(),
                ],

                'CaptchaView' => [
                    'class'        => \rock\snippets\CaptchaView::className(),
                ],

                'Thumb' => [
                    'class'        => \rock\snippets\Thumb::className(),
                ],


                'ActiveForm' => [
                    'class' => \rock\snippets\html\ActiveForm::className(),
                ]
            ]
        ],

        'execute' => [
            'class' => \rock\execute\CacheExecute::className(),
            'path' => '@common/runtime/execute'
        ],

        'i18n' => [
            'class' => \rock\i18n\i18n::className(),
            'pathsDicts' => [
                'ru' => [
                    '@common/lang/ru/lang.php',
                    '@common/lang/ru/validate.php',
                ],
                'en' => [
                    '@common/lang/en/lang.php',
                    '@common/lang/en/validate.php',
                ]
            ],
            'locale' => Rock::$app->language
        ],
        'date' => [
            'class' => \rock\date\DateTime::className(),
            'locale' => Rock::$app->language,
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
            'locale' => Rock::$app->language
        ],
        'response' => [
            'class' => \rock\response\Response::className(),
            'singleton' => true,
            'locale' => Rock::$app->language
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

        'imageProvider' => [
            'class' => \rock\image\ImageProvider::className(),
            'adapter' => [
                'class' => FileManager::className(),
                'adapter' => new Local(Alias::getAlias('@assets/images')),
                'cache' => new Adapter(new Local(Alias::getAlias('@common.runtime/filesystem')), 'images.tmp')
            ],
            'adapterCache' => [
                'class' => FileManager::className(),
                'adapter' => new Local(Alias::getAlias('@assets/cache')),
                'cache' => new Adapter(new Local(Alias::getAlias('@common.runtime/filesystem')), 'image_cache.tmp')
            ],
        ],
        'csrf' => [
            'class' => \rock\csrf\CSRF::className(),
        ],
        'url' => [
            'class' => \rock\url\Url::className(),
        ],
        'validate' => [
            'class' => \rock\validate\Validate::className(),
            'locale' => Rock::$app->language,
        ],
        'activeValidate' => [
            'class' => \rock\validate\ActiveValidate::className(),
            'locale' => Rock::$app->language,
        ],
        'captcha' => [
            'class' => \rock\captcha\Captcha::className(),
            // Captcha string length
            'length' => 0,
            // Noise white
            'whiteNoiseDensity' => 1 / 6,
            // Noise black
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
        'log' => [
            'class' => \rock\log\Log::className(),
        ],

        'behavior' => [
            'class' => \rock\components\Behavior::className(),
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
            'adapter' => [
                'class' => FileManager::className(),
                'adapter' => new Local(Alias::getAlias('@assets/images')),
                'cache' => new Adapter(new Local(Alias::getAlias('@common.runtime/filesystem')), 'images.tmp')
            ],
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
    require(__DIR__ . '/widgets.php')
    //require(__DIR__ . '/snippets.php')
);
