<?php
namespace rock\i18n;

use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\helpers\StringHelper;
use rock\Rock;

class i18n implements ObjectInterface, i18nInterface
{
    use ObjectTrait;

    /**
     * @var array
     */
    public $pathsDicts = [];
    /** @var string  */
    public $locale = self::EN;
    public $category = 'lang';
    public $removeBraces = true;
    protected static $data = [];

    public function init()
    {
        if ($this->locale instanceof \Closure) {
            $this->locale = call_user_func($this->locale, $this);
        }
        $this->loadDicts($this->pathsDicts);
    }

    public function loadDicts(array $dicts)
    {
        if (!empty(static::$data)){
            return;
        }
        foreach ($dicts as $lang => $paths) {
            $total = [];
            foreach ($paths as $path) {
                $path = Rock::getAlias($path);
                if (!file_exists($path) || (!$data = require($path)) || !is_array($data)) {
                    throw new i18nException(i18nException::UNKNOWN_FILE, ['path' => $path]);
                    break 2;
                }
                $context         = basename($path, '.php');
                $total[$context] = array_merge(Helper::getValueIsset($total[$context], []), $data);
            }
            static::$data[$lang] = array_merge(Helper::getValueIsset(static::$data[$lang], []), $total);
        }
    }

    public function locale($locale = self::EN)
    {
        if (!isset($locale)) {
            return $this;
        }
        $this->locale = $locale;
        return $this;
    }

    public function category($category = 'lang')
    {
        if (!isset($category)) {
            return $this;
        }
        $this->category = $category;
        return $this;
    }

    public function removeBraces($removeBraces = false)
    {
        $this->removeBraces = $removeBraces;
        return $this;
    }

    /**
     * Get string by lang
     *
     * ```php
     * i18n::translate('bar.foo');
     * i18n::translate(['bar', 'foo']);
     * ```
     *
     * @param string|array $keys        - chain keys
     * @param array        $placeholders
     * @return null|string
     */
    public function translate($keys, array $placeholders = [])
    {
        if (!$result = $this->translateInternal($keys, $placeholders)) {
            return null;
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getAll(array $only = [], array $exclude = [])
    {
        $data = static::$data;
        if (isset($this->locale)) {
            $data = $data[$this->locale];
        }
        return ArrayHelper::only($data, $only, $exclude);
    }

    /**
     * Exists lang.
     *
     * @param string|array $keys chain keys
     * @return bool
     */
    public function exists($keys)
    {
        if (!isset(static::$data[$this->locale][$this->category])) {
            static::$data[$this->locale][$this->category] = [];
        }
        return (bool)ArrayHelper::getValue(static::$data[$this->locale][$this->category], $keys);
    }

    /**
     * Add lang.
     *
     * ```php
     * i18n::add('en.lang.foo', 'hello {{placeholder}}');
     * i18n::add(['en', 'lang', 'foo'], 'hello {{placeholder}}');
     * ```
     *
     * @param string|array $keys chain keys
     * @param mixed  $value
     */
    public function add($keys, $value)
    {
        if (!isset(static::$data[$this->locale][$this->category])) {
            static::$data[$this->locale][$this->category] = [];
        }
        ArrayHelper::setValue(static::$data[$this->locale][$this->category], !is_array($keys) ? explode('.', $keys) : $keys, $value);
    }

    /**
     * Removes a lang.
     *
     * ```php
     * i18n::remove('foo.bar');
     * i18n::remove(['foo', 'bar']);
     * ```
     *
     * @param string|array $keys chain keys
     */
    public function remove($keys)
    {
        if (!isset(static::$data[$this->locale][$this->category])) {
            static::$data[$this->locale][$this->category] = [];
        }
        ArrayHelper::removeValue(static::$data[$this->locale][$this->category], !is_array($keys) ? explode('.', $keys) : $keys);
    }

    public function load(array $data)
    {
        static::$data = $data;
    }

    public function clear()
    {
        static::$data = [];
    }

    /**
     * Prepare lang
     *
     * @param string|array $keys chain keys
     * @param array        $placeholders
     * @throws i18nException
     * @return mixed|null
     */
    protected function translateInternal($keys, array $placeholders = [])
    {
        if (!isset(static::$data[$this->locale][$this->category])) {
            static::$data[$this->locale][$this->category] = [];
        }
        $result = ArrayHelper::getValue(static::$data[$this->locale][$this->category], $keys);
        if (!isset($result)) {
            $keys = is_array($keys) ? implode('][', $keys) : $keys;
            throw new i18nException(i18nException::UNKNOWN_I18N, ['name' => "{$this->category}[{$keys}]"]);
        }

        return StringHelper::replace($result, $placeholders, $this->removeBraces);
    }
}