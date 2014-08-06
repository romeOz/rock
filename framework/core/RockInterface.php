<?php

namespace rock;


/**
 * "Rock"
 *
 * @property-read string $language
 * @property-read string[] $allowLanguages
 * @property-read string $charset
 * @property-read \rock\base\Controller $currentController
 * @property-read string $name
 * @property-read \rock\cache\CacheInterface                                            $cache
 * @property-read \rock\db\Connection                                           $db
 * @property-read \rock\event\Event                                             $event
 * @property-read \rock\file\FileManager                                         $file
 * @property-read \rock\template\Template                                       $template
 * @property-read \rock\request\Request                                         $request
 * @property-read \rock\user\User                                               $user
 * @property-read \rock\url\Url                                                 $url
 * @property-read \rock\session\Session                                         $session
 * @property-read \rock\cookie\Cookie                                                                $cookie
 * @property-read \rock\i18n\i18n                                               $i18n
 * @property-read \rock\mail\Mail                                               $mail
 * @property-read \rock\route\Route                                             $route
 * @property-read \rock\token\Token                                             $token
 * @property-read \rock\execute\Execute                                         $eval
 * @property-read \rock\helpers\Trace                                             $trace
 * @property-read \rock\captcha\Captcha                                         $captcha
 * @property-read \rock\date\DateTime                                               $date
 * @property-read \rock\db\ActiveDataProvider                                 $activeData
 * @property-read \rock\image\DataProvider                                       $dataImage
 * @property-read \rock\response\Response                                       $response
 * @property-read \rock\log\Log                                                 $log
 * @property-read \rock\validation\Validation                                                 $validation
 * @property-read \rock\di\Container                                            $di
 * @property-read \rock\rbac\RBAC                                            $rbac
 * @property-read \rock\markdown\Markdown                                            $markdown
 * @property-read \rock\security\Security                                            $security
 * @property-read \rock\authclient\Collection   $authClientCollection
 *
 */
interface RockInterface 
{

} 