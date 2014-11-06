<?php
namespace rock\template;

use rock\base\ComponentsInterface;
use rock\base\ComponentsTrait;
use rock\base\Config;
use rock\base\Snippet;
use rock\cache\CacheInterface;
use rock\event\Event;
use rock\helpers\ArrayHelper;
use rock\helpers\File;
use rock\helpers\Helper;
use rock\helpers\Html;
use rock\helpers\Json;
use rock\helpers\Numeric;
use rock\helpers\Serialize;
use rock\helpers\String;
use rock\Rock;
use rock\template\filters\ConditionFilter;

class Template implements ComponentsInterface
{
    use ComponentsTrait;

    const ESCAPE = 1;
    const STRIP_TAGS = 2;
    const TO_TYPE = 4;
    const ENGINE_ROCK = 1;
    const ENGINE_PHP = 2;

    const EVENT_BEFORE_TEMPLATE = 'beforeTemplate';
    const EVENT_AFTER_TEMPLATE = 'afterTemplate';

    /**
     * @event Event an event that is triggered by {@see \rock\template\Template::beginPage()}.
     */
    const EVENT_BEGIN_PAGE = 'beginPage';
    /**
     * @event Event an event that is triggered by {@see \rock\template\Template::endPage()}.
     */
    const EVENT_END_PAGE = 'endPage';

    /**
     * @event Event an event that is triggered by {@see \rock\template\Template::beginBody()}.
     */
    const EVENT_BEGIN_BODY = 'beginBody';
    /**
     * @event Event an event that is triggered by {@see \rock\template\Template::endBody()}.
     */
    const EVENT_END_BODY = 'endBody';
    /**
     * The location of registered JavaScript code block or files.
     * This means the location is in the head section.
     */
    const POS_HEAD = 1;
    /**
     * The location of registered JavaScript code block or files.
     * This means the location is at the beginning of the body section.
     */
    const POS_BEGIN = 2;
    /**
     * The location of registered JavaScript code block or files.
     * This means the location is at the end of the body section.
     */
    const POS_END = 3;
    /**
     * Array global placeholders of variables template engine.
     *
     * @var array
     */
    protected static $placeholders = [];
    protected static $resources = [];
    protected static $data = [];
    private static $_conditionNames = [];
    private static $_inlineConditionNames;
    /**
     * Mapping extensions with engines.
     *
     * @var array
     */
    public $engines = [
        self::ENGINE_ROCK => 'html',
        self::ENGINE_PHP => 'php',
    ];
    /**
     * Use of engine default.
     *
     * @var int
     */
    public $defaultEngine = self::ENGINE_ROCK;
    /**
     * Collection filters.
     *
     * @var array
     */
    public $filters = [];
    /**
     * Collection extensions.
     *
     * @var array
     */
    public $extensions = [];
    public $handlerLink;
    /**
     * Is mode auto-escaping.
     *
     * @var int|bool
     */
    public $autoEscape = self::ESCAPE;
    /**
     * Automatic serialization value.
     *
     * @var bool
     */
    public $autoSerialize = true;
    /** @var string */
    public $head = '<!DOCTYPE html>';
    /** @var string */
    public $body = '<body>';
    /**
     * @var array the registered link tags.
     * @see registerLinkTag()
     */
    public $linkTags = [];
    /**
     * @var array the registered CSS code blocks.
     * @see registerCss()
     */
    public $css = [];
    /**
     * @var array the registered CSS files.
     * @see registerCssFile()
     */
    public $cssFiles = [];
    /**
     * @var array the registered JS code blocks
     * @see registerJs()
     */
    public $js = [];
    /**
     * @var array the registered JS files.
     * @see registerJsFile()
     */
    public $jsFiles = [];
    /**
     * @var string the page title
     */
    public $title = '';
    /**
     * @var array the registered meta tags.
     * @see registerMetaTag()
     */
    public $metaTags = [];
    /**
     * Instance Controller where render template.
     *
     * @var object
     */
    public $context;
    /** @var \rock\cache\CacheInterface|null */
    public $cache = 'cache';
    public $cachePlaceholders = [];
    /**
     * Array local placeholders of variables template engine
     *
     * @var array
     */
    protected $localPlaceholders = [];
    protected $oldPlaceholders = [];
    protected $path;

    /**
     * Rendering layout.
     *
     * @param string      $path path to layout
     * @param array       $placeholders
     * @param object|null $context
     * @return string
     */
    public function render($path, array $placeholders = [], $context = null)
    {
        if (isset($context)) {
            $this->context = $context;
        }
        if (!$this->before($path)) {
            return null;
        }
        list($cacheKey, $cacheExpire, $cacheTags) = $this->calculateCacheParams($placeholders);
        // Get cache
        if (($resultCache = $this->getCache($cacheKey)) !== false) {
            if ($this->after($path, $resultCache) === false) {
                return null;
            }

            return $resultCache;
        }
        $result = $this->renderInternal($path, $placeholders);
        foreach (['jsFiles', 'js', 'linkTags', 'cssFiles', 'css', 'linkTags', 'title', 'metaTags', 'head'] as $property)
        {
            if ($this->$property instanceof \Closure) {
                $this->$property = call_user_func($this->$property, $this);
            }
        }
        $result = implode("\n", [$this->beginPage(), $this->beginBody(), $result, $this->endBody(), $this->endPage()]);
        // Set cache
        $this->setCache($cacheKey, $result, $cacheExpire, $cacheTags);
        if ($this->after($path, $result) === false) {
            return null;
        }

        return $result;
    }

    protected function calculateCacheParams(array &$params = [])
    {
        if (empty($params) || empty($params['cacheKey'])) {
            unset($params['cacheKey'], $params['cacheExpire'], $params['cacheTags']);

            return [null, null, null];
        }
        $cacheKey = $params['cacheKey'];
        $cacheExpire = Helper::getValueIsset($params['cacheExpire'], 0);
        $cacheTags = Helper::getValue($params['cacheTags']);
        unset($params['cacheKey'], $params['cacheExpire'], $params['cacheTags']);

        return [$cacheKey, $cacheExpire, $cacheTags];
    }

    /**
     * Get the content from the cache
     *
     * @param string|null $key
     * @return bool
     */
    protected function getCache($key = null)
    {
        $this->cache = is_string($this->cache) ? Rock::factory($this->cache) : $this->cache;
        if ($this->cache instanceof CacheInterface && isset($key) &&
            ($returnCache = $this->cache->get($key)) !== false
        ) {
            if (is_array($returnCache) && isset($returnCache['placeholders'], $returnCache['result'])) {
                $this->addMultiPlaceholders($returnCache['placeholders'], true);
                $returnCache = $returnCache['result'];
            }

            return $returnCache;
        }

        return false;
    }

    /**
     * Caching template.
     *
     * @param null $key
     * @param null $value
     * @param int  $expire
     * @param null $tags
     */
    protected function setCache($key = null, $value = null, $expire = 0, $tags = null)
    {
        $this->cache = is_string($this->cache) ? Rock::factory($this->cache) : $this->cache;
        if ($this->cache instanceof CacheInterface && isset($key)) {
            if (!empty($this->cachePlaceholders)) {
                $result = $value;
                $value = [];
                $value['result'] = $result;
                $value['placeholders'] = $this->cachePlaceholders;
            }
            $this->cache->set(
                $key,
                $value,
                $expire,
                is_string($tags) ? explode(',', $tags) : $tags
            );
            $this->cachePlaceholders = [];
        }
    }

    /**
     * Adding placeholders
     *
     * @param array $placeholders placeholders
     * @param bool  $global       globally placeholder
     * @return mixed
     */
    public function addMultiPlaceholders(array $placeholders, $global = false)
    {
        if ($global === true) {
            static::$placeholders = array_merge(static::$placeholders, $placeholders);

            return;
        }
        $this->localPlaceholders = $this->oldPlaceholders = array_merge($this->localPlaceholders, $placeholders);
    }

    /**
     * @param string $path path to layout/chunk.
     * @param array  $placeholders
     * @throws TemplateException
     * @return string
     */
    protected function renderInternal($path, array $placeholders = [])
    {
        $path = Rock::getAlias($path, ['lang' => Rock::$app->language]);
        if (!pathinfo($path, PATHINFO_EXTENSION)) {
            $path .= '.' . $this->engines[$this->defaultEngine];
        }
        $path = File::normalizePath($path);
        // Current path
        if (strpos($path, DIRECTORY_SEPARATOR) === false && $this->path) {
            $path = dirname($this->path) . DIRECTORY_SEPARATOR . $path;
        }
        $this->path = $path;
        if (!file_exists($path)) {
            throw new TemplateException(TemplateException::UNKNOWN_FILE, ['path' => $path]);
        }
        if (current(array_keys($this->engines, pathinfo($path, PATHINFO_EXTENSION))) === self::ENGINE_PHP) {
            $this->addMultiPlaceholders($placeholders ?: []);

            return $this->renderPhpFile($path);
        } else {
            return $this->replace(file_get_contents($path), $placeholders);
        }
    }

    protected function renderPhpFile($_path_)
    {
        ob_start();
        ob_implicit_flush(false);
        require($_path_);

        return ob_get_clean();
    }

    /**
     * Replace variables template (chunk, snippet...) on content.
     *
     * @param string $code         current template with variables template.
     * @param array  $placeholders array placeholders of variables template.
     * @return string
     */
    public function replace($code, array $placeholders = [])
    {
        $code = Helper::toType($code);
        if (empty($code) || !is_string($code)) {
            return $code;
        }
        if (!empty($placeholders) && is_array($placeholders)) {
            $this->addMultiPlaceholders($placeholders);
        }
        /*
         * Remove tpl-comment
         * ```
         * {* Comment about *}
         * ```
         */
        $code = preg_replace('/\{\*.*?\*\}/is', "", $code);
        $code = preg_replace_callback(
            '/
                (?P<beforeSkip>\{\!\\s*)?\[\[
                (?P<escape>\!)?
                (?P<type>[\#\%\~]?|\+{1,2}|\*{1,2}|\${1,2})					# search type of variable template
                (?P<name>[\\w\-\/\\\.@]+)							# name of variable template [\w, -, \, .]
                (?:[^\[\]]++ | \[(?!\[) | \](?!\]) | (?R))*		# possible recursion
                \]\](?P<afterSkip>\\s*\!\})?
            /iux',
            [$this, 'replaceCallback'],
            $code
        );

        return $code;
    }

    /**
     * Marks the beginning of a page.
     */
    public function beginPage()
    {
        Event::trigger($this, self::EVENT_BEGIN_PAGE);

        return $this->renderHeadHtml();
    }

    /**
     * Renders the content to be inserted in the head section.
     * The content is rendered using the registered meta tags, link tags, CSS/JS code blocks and files.
     *
     * @return string the rendered content
     */
    protected function renderHeadHtml()
    {
        $lines = [];
        $lines[] = $this->head;
        $lines[] = '<head>';
        $lines[] = Html::tag('title', $this->title);
        if (!empty($this->metaTags)) {
            $lines[] = implode("\n", $this->metaTags);
        }
        if (!empty($this->linkTags)) {
            $lines[] = implode("\n", $this->linkTags);
        }
        if (!empty($this->cssFiles[self::POS_HEAD])) {
            $lines[] = implode("\n", $this->cssFiles[self::POS_HEAD]);
        }
        if (!empty($this->css)) {
            $lines[] = implode("\n", $this->css);
        }
        if (!empty($this->jsFiles[self::POS_HEAD])) {
            $lines[] = implode("\n", $this->jsFiles[self::POS_HEAD]);
        }
        if (!empty($this->js[self::POS_HEAD])) {
            $lines[] = Html::script(implode("\n", $this->js[self::POS_HEAD]), ['type' => 'text/javascript']);
        }
        $lines[] = '</head>';

        return empty($lines) ? '' : implode("\n", $lines);
    }

    /**
     * Marks the beginning of an HTML body section.
     */
    public function beginBody()
    {
        Event::trigger($this, self::EVENT_BEGIN_BODY);

        return $this->renderBodyBeginHtml();
    }

    /**
     * Renders the content to be inserted at the beginning of the body section.
     * The content is rendered using the registered JS code blocks and files.
     *
     * @return string the rendered content
     */
    protected function renderBodyBeginHtml()
    {
        $lines = [$this->body];
        if (!empty($this->jsFiles[self::POS_BEGIN])) {
            $lines[] = implode("\n", $this->jsFiles[self::POS_BEGIN]);
        }
        if (!empty($this->js[self::POS_BEGIN])) {
            $lines[] = Html::script(implode("\n", $this->js[self::POS_BEGIN]), ['type' => 'text/javascript']);
        }

        return empty($lines) ? '' : implode("\n", $lines);
    }

    /**
     * Marks the ending of an HTML body section.
     */
    public function endBody()
    {
        Event::trigger($this, self::EVENT_END_BODY);

        return $this->renderBodyEndHtml();
    }

    /**
     * Renders the content to be inserted at the end of the body section.
     * The content is rendered using the registered JS code blocks and files.
     *
     * @return string the rendered content
     */
    protected function renderBodyEndHtml()
    {
        $lines = [];
        if (!empty($this->cssFiles[self::POS_END])) {
            $lines[] = implode("\n", $this->cssFiles[self::POS_END]);
        }
        if (!empty($this->jsFiles[self::POS_END])) {
            $lines[] = implode("\n", $this->jsFiles[self::POS_END]);
        }
        $scripts = [];
        if (!empty($this->js[self::POS_END])) {
            $scripts[] = implode("\n", $this->js[self::POS_END]);
        }
        if (!empty($scripts)) {
            $lines[] = Html::script(implode("\n", $scripts), ['type' => 'text/javascript']);
        }
        $lines[] = '</body>';

        return empty($lines) ? '' : implode("\n", $lines);
    }

    /**
     * Marks the ending of an HTML page.
     */
    public function endPage()
    {
        Event::trigger($this, self::EVENT_END_PAGE);
        $this->clear();

        return '</html>';
    }

    /**
     * Clears up the registered meta tags, link tags, css/js scripts and files.
     */
    public function clear()
    {
        $this->metaTags = [];
        $this->linkTags = [];
        $this->css = [];
        $this->cssFiles = [];
        $this->js = [];
        $this->jsFiles = [];
        static::$placeholders = [];
        $this->localPlaceholders = [];
    }

    /**
     * Has chunk
     *
     * @param string $path path to chunk.
     * @return bool
     */
    public function hasChunk($path)
    {
        $path = Rock::getAlias($path, ['lang' => $this->Rock->language]);
        if (!pathinfo($path, PATHINFO_EXTENSION)) {
            $path .= '.' . $this->engines[$this->defaultEngine];
        }

        return file_exists($path);
    }

    /**
     * Get local/global placeholder and resource.
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($this->hasPlaceholder($name)) {
            return $this->getPlaceholder($name);
        }
        if ($this->hasPlaceholder($name, true)) {
            return $this->getPlaceholder($name, true, true);
        }
        if ($this->hasResource($name)) {
            return $this->getResource($name);
        }

        return null;
    }

    /**
     * Adding local placeholder.
     *
     * @param string $name name of placeholder.
     * @param        $value
     */
    public function __set($name, $value)
    {
        $this->addPlaceholder($name, $value);
    }

    /**
     * Exists placeholder
     *
     * @param string $name name of placeholder.
     * @param bool   $global
     * @return bool
     */
    public function hasPlaceholder($name, $global = false)
    {
        if ($global === true) {
            return (bool)ArrayHelper::getValue(static::$placeholders, $name);
        }

        return (bool)ArrayHelper::getValue($this->localPlaceholders, $name);
    }

    /**
     * Get placeholder.
     *
     * @param string        $name   name of placeholder
     * @param int|bool|null $autoEscape
     * @param bool          $global globally placeholder
     * @return string|null
     */
    public function getPlaceholder($name, $autoEscape = true, $global = false)
    {
        if ($global === true) {
            return $this->autoEscape(ArrayHelper::getValue(static::$placeholders, $name), $autoEscape);
        }

        return $this->autoEscape(ArrayHelper::getValue($this->localPlaceholders, $name), $autoEscape);
    }

    /**
     * Autoescape vars of template engine
     *
     * @param mixed    $value
     * @param bool|int $const
     * @return mixed
     */
    public function autoEscape($value, $const = true)
    {
        if (is_array($value)) {
            $hash = Helper::hash($value, Helper::SERIALIZE_JSON);
            if (isset(static::$data[$hash])) {
                return static::$data[$hash];
            }

            return static::$data[$hash] =
                ArrayHelper::map(
                    $value,
                    function ($value) use ($const) {
                        return $this->escape($value, $const);
                    },
                    true
                );
        }

        return $this->escape($value, $const);
    }

    protected function escape($value, $const = true)
    {
        if (!isset($value)) {
            return null;
        }
        if ($const === true) {
            $const = $this->autoEscape;
        }
        if ($const === false) {
            return $value;
        }
        if ($const & self::TO_TYPE) {
            $value = Helper::toType($value);
        }
        if (!is_string($value)) {
            return $value;
        }
        if ($const & self::STRIP_TAGS) {
            $value = strip_tags($value);
        }
        if ($const & self::ESCAPE) {
            $value = String::encode($value);
        }

        return $value;
    }

    /**
     * Has resource by name.
     *
     * @param string $name name of resource.
     * @return bool
     */
    public function hasResource($name)
    {
        return (bool)ArrayHelper::getValue(static::$resources, $name);
    }

    /**
     * Get resource.
     *
     * @param string $name name of resource.
     * @param bool   $autoEscape
     * @return array|mixed|null|string
     */
    public function getResource($name, $autoEscape = true)
    {
        return $this->autoEscape(ArrayHelper::getValue(static::$resources, $name), $autoEscape);
    }

    /**
     * Adding placeholder.
     *
     * @param string $name   name of placeholder
     * @param mixed  $value  value
     * @param bool   $global globally placeholder
     */
    public function addPlaceholder($name, $value = null, $global = false)
    {
        if ($global === true) {
            static::$placeholders[$name] =
                isset(static::$placeholders[$name]) && is_array(static::$placeholders[$name])
                    ? array_merge(static::$placeholders[$name], (array)$value)
                    : $value;

            return;
        }
        $this->localPlaceholders[$name] =
            isset($this->localPlaceholders[$name]) && is_array($this->localPlaceholders[$name])
                ? array_merge($this->localPlaceholders[$name], (array)$value)
                : $value;
        $this->oldPlaceholders = $this->localPlaceholders;
    }

    /**
     * Exists local/global placeholder and resource.
     *
     * @param string $name name of placeholder/resource.
     * @return bool
     */
    public function __isset($name)
    {
        if ($this->hasPlaceholder($name)) {
            return true;
        }
        if ($this->hasPlaceholder($name, true)) {
            return true;
        }
        if ($this->hasResource($name)) {
            return true;
        }

        return false;
    }

    /**
     * Removing local placeholder.
     *
     * @param string $name name of placeholder.
     */
    public function __unset($name)
    {
        $this->removePlaceholder($name);
    }

    /**
     * Deleting placeholder.
     *
     * @param string $name name of placeholder.
     * @param bool   $global globally placeholder
     */
    public function removePlaceholder($name, $global = false)
    {
        if (empty($name)) {
            return;
        }
        if ($global === true) {
            unset(static::$placeholders[$name]);

            return;
        }
        unset($this->localPlaceholders[$name]);
    }

    /**
     * Get all placeholders.
     *
     * @param int|bool $autoEscape
     * @param bool     $global globally placeholder.
     * @param array    $only
     * @param array    $exclude
     * @return array
     */
    public function getAllPlaceholders($autoEscape = true, $global = false, array $only = [], array $exclude = [])
    {
        if ($global === true) {
            return $this->autoEscape(ArrayHelper::only(static::$placeholders, $only, $exclude), $autoEscape);
        }

        return $this->autoEscape(ArrayHelper::only($this->localPlaceholders, $only, $exclude), $autoEscape);
    }

    /**
     * Adding multi-resources.
     *
     * @param array $resources
     */
    public function addMultiResources(array $resources)
    {
        foreach ($resources as $name => $value) {
            $this->addResource($name, $value);
        }
    }

    /**
     * Adding resource.
     *
     * @param string $name  name of resource
     * @param mixed $value value of resource
     */
    public function addResource($name, $value)
    {
        static::$resources[$name] = $value;
    }

    /**
     * Get all resources.
     *
     * @param bool  $autoEscape
     * @param array $only
     * @param array $exclude
     * @return array|mixed|null|string
     */
    public function getAllResources($autoEscape = true, array $only = [], array $exclude = [])
    {
        return $this->autoEscape(ArrayHelper::only(static::$resources, $only, $exclude), $autoEscape);
    }

    /**
     * Deleting resource.
     *
     * @param string $name name of resource
     */
    public function removeResource($name)
    {
        unset(static::$resources[$name]);
    }

    /**
     * Deleting all resources.
     */
    public function removeAllResource()
    {
        static::$resources = [];
    }

    /**
     * Removing prefix by param.
     *
     * @param string $value value of param
     * @return string|null
     */
    public function removePrefix($value)
    {
        if (empty($value)) {
            return null;
        }

        return preg_replace('/\@[A-Z\-\_]+/', '', $value);
    }

    /**
     * Calculate for adding placeholders.
     *
     * @param array $placeholders
     * @return array
     *
     * ```php
     * (new \rock\Template)->calculateAddPlaceholders(['foo', 'bar' => 'text']); // ['foo' => 'text', 'bar' => 'text']
     * ```
     */
    public function calculateAddPlaceholders(array $placeholders = [])
    {
        if (empty($placeholders)) {
            return [];
        }
        $result = [];
        foreach ($placeholders as $name => $value) {
            if (is_int($name)) {
                if ($this->hasPlaceholder($value)) {
                    $result[$value] = $this->getPlaceholder($value, false);
                } elseif (isset($this->oldPlaceholders[$value])) {
                    $result[$value] = $this->oldPlaceholders[$value];
                }
                continue;
            }
            $result[$name] = $value;
        }

        return $result;
    }

    /**
     * Registers a meta tag.
     *
     * @param array  $options the HTML attributes for the meta tag.
     * @param string $key     the key that identifies the meta tag. If two meta tags are registered
     *                        with the same key, the latter will overwrite the former. If this is null, the new meta tag
     *                        will be appended to the existing ones.
     */
    public function registerMetaTag($options, $key = null)
    {
        if ($key === null) {
            $this->metaTags[] = $this->renderWrapperTag(Html::tag('meta', '', $options), $options);
        } else {
            $this->metaTags[$key] = $this->renderWrapperTag(Html::tag('meta', '', $options), $options);
        }
    }

    private function renderWrapperTag($value, array $options = [])
    {
        if (empty($options['wrapperTpl'])) {
            return $value;
        }
        $value = $this->replaceByPrefix($options['wrapperTpl'], ['output' => $value]);

        return $value;
    }

    /**
     * Replace inline tpl.
     *
     * @param string $value value
     * @param array  $placeholders
     * @return string
     */
    public function replaceByPrefix($value, array $placeholders = [])
    {
        $dataPrefix = $this->getNamePrefix($value);
        if (strtolower($dataPrefix['prefix']) === 'inline') {
            $template = clone $this;
            $result = $template->replace($dataPrefix['value'], $placeholders);

            return $result;
        }
        $result = $this->getChunk(trim($value), $placeholders);

        return $result;
    }

    /**
     * Get name prefix by param.
     *
     * @param string $value value of param
     * @return array|null
     */
    public function getNamePrefix($value)
    {
        if (empty($value)) {
            return null;
        }
        preg_match('/(?:\@(?P<prefix>INLINE))?(?P<value>.+)/is', $value, $matches);

        return [
            'prefix' => Helper::getValue($matches['prefix']),
            'value' => Helper::getValue($matches['value'])
        ];
    }

    /**
     * Rendering chunk.
     *
     * @param string $path path to chunk.
     * @param array  $placeholders
     * @return string
     */
    public function getChunk($path, array $placeholders = [])
    {
        $template = clone $this;
        $template->removeAllPlaceholders();
        list($cacheKey, $cacheExpire, $cacheTags) = $template->calculateCacheParams($placeholders);
        // Get cache
        if (($resultCache = $template->getCache($cacheKey)) !== false) {
            return $resultCache;
        }
        $result = $template->renderInternal($path, $placeholders);
        // Set cache
        $template->setCache($cacheKey, $result, $cacheExpire, $cacheTags);

        return $result;
    }

    /**
     * Deleting all placeholders
     *
     * @param bool $global
     */
    public function removeAllPlaceholders($global = false)
    {
        if ($global === true) {
            static::$placeholders = [];

            return;
        }
        $this->localPlaceholders = [];
    }

    /**
     * Registers a link tag.
     *
     * @param array  $options the HTML attributes for the link tag.
     * @param string $key     the key that identifies the link tag. If two link tags are registered
     *                        with the same key, the latter will overwrite the former. If this is null, the new link tag
     *                        will be appended to the existing ones.
     */
    public function registerLinkTag($options, $key = null)
    {
        if ($key === null) {
            $this->linkTags[] = $this->renderWrapperTag(Html::tag('link', '', $options), $options);
        } else {
            $this->linkTags[$key] = $this->renderWrapperTag(Html::tag('link', '', $options), $options);
        }
    }

    /**
     * Registers a CSS code block.
     *
     * @param string $css     the CSS code block to be registered
     * @param array  $options the HTML attributes for the style tag.
     * @param string $key     the key that identifies the CSS code block. If null, it will use
     *                        $css as the key. If two CSS code blocks are registered with the same key, the latter
     *                        will overwrite the former.
     */
    public function registerCss($css, $options = [], $key = null)
    {
        $key = $key ?: md5($css);
        $this->css[$key] = Html::style($css, $options);
    }

    /**
     * Registers a CSS file.
     *
     * @param string $url     the CSS file to be registered.
     * @param array  $options the HTML attributes for the link tag.
     * @param string $key     the key that identifies the CSS script file. If null, it will use
     *                        $url as the key. If two CSS files are registered with the same key, the latter
     *                        will overwrite the former.
     */
    public function registerCssFile($url, $options = [], $key = null)
    {
        $url = Rock::getAlias($url);
        $key = $key ?: $url;
        $position = isset($options['position']) ? $options['position'] : self::POS_HEAD;
        unset($options['position']);
        $this->cssFiles[$position][$key] = $this->renderWrapperTag(Html::cssFile($url, $options), $options);
    }

    /**
     * Registers a JS code block.
     *
     * @param string  $js       the JS code block to be registered
     * @param integer $position the position at which the JS script tag should be inserted
     *                          in a page. The possible values are:
     *
     * - `POS_HEAD`: in the head section
     * - `POS_BEGIN`: at the beginning of the body section
     * - `POS_END`: at the end of the body section
     *
     * @param string  $key      the key that identifies the JS code block. If null, it will use
     *                          $js as the key. If two JS code blocks are registered with the same key, the latter
     *                          will overwrite the former.
     */
    public function registerJs($js, $position = self::POS_HEAD, $key = null)
    {
        $key = $key ?: md5($js);
        $this->js[$position][$key] = $js;
    }

    /**
     * Registers a JS file.
     *
     * @param string $url     the JS file to be registered.
     * @param array  $options the HTML attributes for the script tag. A special option
     *                        named "position" is supported which specifies where the JS script tag should be inserted
     *                        in a page. The possible values of "position" are:
     *
     * - `POS_HEAD`: in the head section
     * - `POS_BEGIN`: at the beginning of the body section
     * - `POS_END`: at the end of the body section. This is the default value.
     *
     * @param string $key     the key that identifies the JS script file. If null, it will use
     *                        $url as the key. If two JS files are registered with the same key, the latter
     *                        will overwrite the former.
     */
    public function registerJsFile($url, $options = [], $key = null)
    {
        $url = Rock::getAlias($url);
        $key = $key ?: $url;
        $position = isset($options['position']) ? $options['position'] : self::POS_END;
        unset($options['position']);
        $this->jsFiles[$position][$key] = $this->renderWrapperTag(Html::jsFile($url, $options), $options);
    }

    /**
     * This method is invoked right before an chunk/snippet is executed.
     *
     * The method will trigger the {@see \rock\base\Controller::EVENT_BEFORE_TEMPLATE} event. The return value of the method
     * will determine whether the action should continue to run.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function before($namen)
     * {
     *     if (parent::before($name)) {
     *         // your custom code here
     *         return true;  // or false if needed
     *     } else {
     *         return false;
     *     }
     * }
     * ```
     *
     * @param string $name the path to chunk/snippet to be executed.
     * @return boolean whether the action should continue to run.
     */
    protected function before($name)
    {
        $event = new TemplateEvent();
        $event->name = $name;
        $this->trigger(self::EVENT_BEFORE_TEMPLATE, $event);
        return $event->isValid;
    }

    /**
     * This method is invoked right after an action is executed.
     *
     * The method will trigger the {@see \rock\base\Controller::EVENT_AFTER_ACTION} event. The return value of the method
     * will be used as the action return value.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function after($name, $result)
     * {
     *     $result = parent::after($name, $result);
     *     // your custom code here
     *     return $result;
     * }
     * ```
     *
     * @param string $name the path to chunk/snippet just executed.
     * @param mixed $result the action return result.
     * @return mixed the processed action result.
     */
    protected function after($name, $result)
    {
        $event = new TemplateEvent();
        $event->name = $name;
        $event->result = $result;
        $this->trigger(self::EVENT_AFTER_TEMPLATE, $event);
        return $event->result;
    }

    /**
     * Callback to replace variables template.
     *
     * @param array $matches array of variables template.
     * @throws TemplateException
     * @return string
     */
    protected function replaceCallback($matches)
    {
        if (!empty($matches['beforeSkip']) && !empty($matches['afterSkip'])) {
            return trim($matches[0], '{!} ');
        }
        // Validate: if count quotes does not parity
        if (!Numeric::isParity(mb_substr_count($matches[0], '`', 'UTF-8'))) {
            return $matches[0];
        }
        $matches[0] = preg_replace_callback(
            '/
                \\s*(?P<sugar> (?!`)\*(?!`) | (?!`)\*\*(?!`) | (?!`)\/(?!`) | (?!`)\%(?!`) |
                \\s+(?!`)mod(?!`)\\s+ | (?!`)\+(?!`) | (?!`)\-(?!`) | (?!`)\|(?!`) | (?!`)\&(?!`) |
                (?!`)\^(?!`) | (?!`)\>\>(?!`) | (?!`)\<\<(?!`) |
                (?!`)\|\|(?!`) | (?!`)\&\&(?!`) | \\s+(?!`)' . $this->_getInlineConditionNames() . '(?!`)\\s+ |`\\s+\?\\s+|`\\s+\:\\s+)\\s*`
            /x',
            [$this, 'replaceSugar'],
            $matches[0]);
        // Replace `=` tpl mnemonics
        $matches[0] = preg_replace('/`([\!\<\>]?)[\=]+`/', '`$1&#61;`', $matches[0]);
        // Replace `text` to ““text””
        $matches[0] = preg_replace(['/=\\s*\`/', '/\`/'], ['=““', '””'], $matches[0]);
        // Replacement of internal recursion on {{{...}}}
        $i = 0;
        $dataRecursive = [];
        $matches[0] = preg_replace_callback(
            '/\“\“(?:[^\“\”]++|(?R))*\”\”/iu',
            function ($value) use (&$dataRecursive, &$i) {
                $key = '{{{' . $i . '}}}';
                $value = current($value);
                $dataRecursive[$key] = $value;
                $i++;

                return $key;
            },
            $matches[0]
        );
        // Search params is variable of template
        $params = $this->_searchParams($matches[0], $dataRecursive);
        // Search of filters (modifiers)
        $filters = $this->_searchFilters($matches[0], $dataRecursive);
        $matches['name'] = trim($matches['name']);
        // Get cache
        list($cacheKey, $cacheExpire, $cacheTags) = $this->calculateCacheParams($params);
        if (($resultCache = $this->getCache($cacheKey)) !== false) {
            return $resultCache;
        }
        $params = Serialize::unserializeRecursive($params);
        $filters = Serialize::unserializeRecursive($filters);
        $escape = !$matches['escape'];
        // chunk
        if ($matches['type'] === '$') {
            $result = $this->getChunk($matches['name'], $params);
            // data of resource
        } elseif ($matches['type'] === '*') {
            $result = $this->getResource(
                $matches['name'],
                Helper::getValueIsset($params['autoEscape'], $escape)
            );
            // config
        } elseif ($matches['type'] === '**') {
            $result = ArrayHelper::getValue(Config::getAll(true), explode('.', $matches['name']));
            // get alias
        } elseif ($matches['type'] === '$$') {
            $result = Rock::getAlias("@{$matches['name']}");
            // local placeholder
        } elseif ($matches['type'] === '+') {
            $result = $this->getPlaceholder(
                $matches['name'],
                Helper::getValueIsset($params['autoEscape'], $escape)
            );
            // global placeholder
        } elseif ($matches['type'] === '++') {
            $result =
                $this->getPlaceholder($matches['name'], Helper::getValueIsset($params['autoEscape'], $escape), true);
            // extensions
        } elseif ($matches['type'] === '#') {
            $result =
                $this->getExtension($matches['name'], $params, Helper::getValueIsset($params['autoEscape'], $escape));
            //  i18n
        } elseif ($matches['type'] === '%') {
            $result = $this->_provideI18n(
                $matches['name'],
                Helper::getValue($params['dataReplace'], []),
                Helper::getValue($params['lang']),
                Helper::getValue($params['category'])
            );
            // link to resource
        } elseif ($matches['type'] === '~') {
            $result = $this->_provideLink($matches['name']);
            // snippet
        } elseif (empty($matches['type'])) {
            $result = $this->getSnippet($matches['name'], $params, $escape);
        } else {
            return $matches[0];
        }
        // Make a filter
        if (!empty($filters)) {
            $result = $this->makeFilter($result, $filters);
        }
        if ($this->autoSerialize) {
            if (is_array($result)) {
                $result = Json::encode($result);
            } elseif (is_object($result) && !is_callable($result)) {
                $result = serialize($result);
            }
        }
        if (!is_scalar($result) && !empty($result)) {
            throw new TemplateException('Wrong type is var: ' . Json::encode($result));
        }
        // Set cache
        $this->setCache(
            $cacheKey,
            $result,
            $cacheExpire,
            $cacheTags
        );

        return $result;
    }

    private function _getInlineConditionNames()
    {
        if (!isset(static::$_inlineConditionNames)) {
            static::$_inlineConditionNames = implode('\\s+|', array_flip($this->_getConditionNames()));
        }

        return static::$_inlineConditionNames;
    }

    private function _getConditionNames()
    {
        if (empty(static::$_conditionNames)) {
            foreach (ConditionFilter::$conditionNames as $names) {
                static::$_conditionNames = array_merge(static::$_conditionNames, $names);
            }
            static::$_conditionNames = array_flip(static::$_conditionNames);
        }

        return static::$_conditionNames;
    }

    /**
     * Search placeholders is variable of template.
     *
     * ```
     * ?<name>=<value>
     * ```
     *
     * @param string $value
     * @param array  $dataRecursive
     * @return array
     */
    private function _searchParams($value, array $dataRecursive)
    {
        preg_match_all(
            '/
                \?
                (?P<name>\\w+)                  # name param
                \\s*\=\\s*
                (?P<value>\{{3}\\d+\}{3}\\s*)*  # value param
                [^\?\[\]]*                      # restriction: is not "?" and not "[" "]"
            /iux',
            $value,
            $matches
        );
        $i = 0;
        $j = 0;
        $result = [];
        if (!isset($matches['name'])) {
            return $result;
        }
        foreach ($matches['name'] as $nameParams) {
            if (!isset($matches['value'][$i]) || !isset($nameParams)) {
                continue;
            }
            $valueParams = mb_substr($dataRecursive[trim($matches['value'][$i])], 2, -2, 'UTF-8');
            // Search prefix
            $valueParams = $this->_searchPrefix($nameParams, $valueParams);
            // to type
            if (is_string($valueParams)) {
                $valueParams = Helper::toType(str_replace('&#61;', '=', $valueParams));
            }
            // If multiple placeholders with the same name, then create to array
            if (isset($result[$nameParams])) {
                if (is_array($result[$nameParams])) {
                    $result[$nameParams][] = $valueParams;
                } else {
                    $result[$nameParams] = [$result[$nameParams], $valueParams];
                }
                ++$j;
            } else {
                $result[$nameParams] = $valueParams;
            }
            ++$i;
        }

        return $result;
    }

    /**
     * Validate: not replace is `@INLINE` prefix exists or parameters then/else.
     *
     * @param string $name  name of param
     * @param string $value value of param
     * @return string
     */
    private function _searchPrefix($name, $value)
    {
        $matches = [];
        // Validate: not replace is @INLINE exists or parameters then/else
        preg_match('/\@(?P<prefix>(?:INLINE|FILE)).+/s', $value, $matches);
        if ((!isset($matches['prefix']) || strtolower($matches['prefix']) !== 'inline')
            && !in_array($name, ['then', 'else'], true)
        ) {
            return $this->replace($value);
        }

        return $value;
    }

    /**
     * Search of filters (modifiers).
     *
     * @param string $value
     * @param array  $dataRecursive
     * @return array
     */
    private function _searchFilters($value, array $dataRecursive)
    {
        // Search of filters (modifiers)
        preg_match_all(
            '/
                \:
                (?P<name>\\w+)											# name of filter
                (?P<value>(?:\s*\&\\w+\\s*\=\\s*\{{3}\\d+\}{3}\\s*)*)	# variables of filter
                [^\:\[\]]*												# restriction: is not ":" and not "[" "]"
            /iux',
            $value,
            $matches
        );
        $i = 0;
        $result = [];
        if (!isset($matches['name'])) {
            return $result;
        }
        foreach ($matches['name'] as $value) {
            if (!isset($matches['value'][$i]) || !isset($value)) {
                continue;
            }
            $result[$value][] = $this->_searchParamsByFilters($matches, $dataRecursive, $i);
            ++$i;
        }

        return $result;
    }

    private function _searchParamsByFilters(array $matches, array $array_recursive, $i)
    {
        // Parsing params of filter
        preg_match_all(
            '/
                \&
                (?P<names>\\w+)			    # name variable of filter
                \\s*\=\\s*
                (?P<values>\{{3}\\d+\}{3})	# value variable of filter
                [^\&]*
            /iux',
            $matches['value'][$i],
            $params
        );
        $j = 0;
        $result = [];
        if (isset($params['names'])) {
            foreach ($params['names'] as $name) {
                if (!isset($params['values'][$j]) || !isset($name)) {
                    continue;
                }
                $result[$name] = mb_substr($array_recursive[trim($params['values'][$j])], 2, -2, 'UTF-8');
                // Search prefix
                $result[$name] = $this->_searchPrefix($name, $result[$name]);
                if (is_string($result[$name])) {
                    $result[$name] = Helper::toType($result[$name]);
                }
                ++$j;
            }
        }

        return $result;
    }

    /**
     * @param string   $name name of extension
     * @param array    $params
     * @param bool|int $autoEscape
     * @return mixed
     */
    public function getExtension($name, array $params = [], $autoEscape = true)
    {
        $result = $this->_getExtensionInternal($name, $params);
        if (!empty($params)) {
            $this->removePlaceholder('params');
        }

        return $this->autoEscape($result, $autoEscape);
    }

    /**
     * @param string $name   name of extension
     * @param array  $params params
     * @throws TemplateException
     * @return mixed
     */
    private function _getExtensionInternal($name = null, array $params = [])
    {
        if (!strstr($name, '.') ||
            (!$names = explode('.', $name)) ||
            count($names) < 2
        ) {
            return null;
        }
        $name = strtolower($names[0]);
        if ($this->extensions[$name] instanceof \Closure) {
            unset($names[0]);

            return call_user_func($this->extensions[$name], array_values($names), $params, $this);
        }

        return null;
    }

    private function _provideI18n($name, $dataReplace = [], $language = null, $category = null)
    {
        $result = Rock::t(
            explode('.', $name),
            $dataReplace,
            $category,
            $language
        );
        if (!empty($params)) {
            $this->removeMultiPlaceholders(['dataReplace', 'lang', 'context']);
        }

        return $result;
    }

    /**
     * Deleting multi-placeholders.
     *
     * @param array $names
     * @param bool  $global globally placeholder
     */
    public function removeMultiPlaceholders(array $names, $global = false)
    {
        if ($global === true) {
            static::$placeholders = array_diff_key(static::$placeholders, array_flip($names));

            return;
        }
        $this->localPlaceholders = array_diff_key($this->localPlaceholders, array_flip($names));
    }

    private function _provideLink($link)
    {
        if (empty($link) || !$this->handlerLink instanceof \Closure) {
            return '#';
        }
        $link = explode('.', $link);

        return call_user_func($this->handlerLink, $link, $this);
    }

    /**
     * Get data from snippet.
     *
     * @param string|\rock\base\Snippet $snippet name of
     *                                           snippet/instance @see \rock\base\Snippet
     * @param array                     $params  params
     * @param bool                      $autoEscape
     * @return mixed
     */
    public function getSnippet($snippet, array $params = [], $autoEscape = true)
    {
        if (!$this->before($snippet)) {
            return null;
        }
        $template = clone $this;
        $template->removeAllPlaceholders();
        $result = $template->getSnippetInternal($snippet, $params, $autoEscape);
        $this->cachePlaceholders = $template->cachePlaceholders;

        $this->after($snippet, $result);
        return $result;
    }

    protected function getSnippetInternal($snippet, array $params = [], $autoEscape = true)
    {
        list($cacheKey, $cacheExpire, $cacheTags) = $this->calculateCacheParams($params);
        if ($snippet instanceof Snippet) {
            if (!empty($params)) {
                $snippet->setProperties($params);
            }
        } else {
            $class = ltrim(Rock::getAlias($snippet, ['lang' => $this->Rock->language]), '\\');
            $params['class'] = $class;
            /** @var \rock\base\Snippet $snippet */
            $snippet = Rock::factory($params);
            if (!$snippet instanceof Snippet) {
                throw new TemplateException(TemplateException::UNKNOWN_SNIPPET, ['name' => $snippet::className()]);
            }
        }
        if ($autoEscape === false) {
            $snippet->autoEscape = false;
        }
        $snippet->template = $this;
        if (!$snippet->beforeSnippet($snippet::className())) {
            return null;
        }
        // Get cache
        if (($resultCache = $this->getCache($cacheKey)) !== false) {
            if (!$snippet->afterSnippet($snippet::className(), $resultCache)) {
                return null;
            }

            return $resultCache;
        }
        $result = $snippet->get();
        $result = $this->autoEscape(
            $result,
            isset($params['autoEscape']) && $params['autoEscape'] === false ? false : $snippet->autoEscape);
        $result = is_string($result)
            ? str_replace(
                ['[[', ']]', '{{{', '}}}', '`', '“', '”'],
                ['&#91;&#91;', '&#93;&#93;', '&#123;&#123;&#123;', '&#125;&#125;&#125;', '&#96;', '&laquo;', '&raquo;'],
                $result
            )
            : $result;
        //  Set cache
        $this->setCache($cacheKey, $result, $cacheExpire, $cacheTags);
        if (!$snippet->afterSnippet($snippet::className(), $result)) {
            return null;
        }

        return $result;
    }

    /**
     * Make filter (modifier).
     *
     * @param string $value   value
     * @param array  $filters array of filters with params
     * @throws TemplateException
     * @return string
     */
    public function makeFilter($value, $filters)
    {
        foreach ($filters as $method => $params) {
            if (empty($params)) {
                $params = [];
            }
            foreach ($params as $_params) {
                if (isset($this->filters[$method]['class'])) {
                    $filterParams = $this->filters[$method];
                    $class = $filterParams['class'];
                    $method = Helper::getValue($filterParams['method'], $method);
                    unset($filterParams['class'], $filterParams['method']);
                    $value = call_user_func([$class, $method], $value, array_merge($filterParams, $_params), $this);
                } elseif (function_exists($method)) {
                    $value = call_user_func_array($method, array_merge([$value], $_params));
                } else {
                    throw new TemplateException(TemplateException::UNKNOWN_FILTER, ['name' => $method]);
                }
            }
        };

        return $value;
    }

    protected function replaceSugar($matches)
    {
        $matches['sugar'] = trim($matches['sugar'], " `\n\t\r");
        switch ($matches['sugar']) {
            case '||':
                return "\n:empty\n&is=`@INLINE";
            case '&&':
                return "\n:notEmpty\n&is=`@INLINE";
            case array_key_exists($matches['sugar'], $this->_getConditionNames()):
                return "\n:if\n&{$matches['sugar']}=`";
            case '?':
                return "`\n&then=`";
            case ':':
                return "`\n&else=`";
            case '+':
                return "\n:formula\n&operator=`+`\n&operand=`";
            case '-':
                return "\n:formula\n&operator=`-`\n&operand=`";
            case '*':
                return "\n:formula\n&operator=`*`\n&operand=`";
            case '/':
                return "\n:formula\n&operator=`/`\n&operand=`";
            case '**':
                return "\n:formula\n&operator=`**`\n&operand=`";
            case '%':
            case 'mod':
                return "\n:formula\n&operator=`%`\n&operand=`";
            case '|':
                return "\n:formula\n&operator=`|`\n&operand=`";
            case '&':
                return "\n:formula\n&operator=`&`\n&operand=`";
            case '^':
                return "\n:formula\n&operator=`^`\n&operand=`";
            case '<<':
                return "\n:formula\n&operator=`<<`\n&operand=`";
            case '>>':
                return "\n:formula\n&operator=`>>`\n&operand=`";
            default:
                return $matches[0];
        }
    }
}