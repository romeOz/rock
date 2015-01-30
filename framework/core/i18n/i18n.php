<?php
namespace rock\i18n;

use rock\base\Alias;
use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\di\Container;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\helpers\StringHelper;

class i18n implements ObjectInterface
{
    use ObjectTrait;

    /**
     * List paths to dicts.
     * @var array
     */
    public $pathsDicts = [];
    /**
     * Default locale.
     * @var string
     */
    public $locale = 'en';
    /**
     * Default category.
     * @var string
     */
    public $category = 'lang';
    /**
     * Remove braces in record.
     * @var bool
     */
    public $removeBraces = true;
    /**
     * Cache records.
     * @var array
     */
    protected static $data = [];

    public function init()
    {
        $this->locale = strtolower($this->locale);
        $this->addDicts($this->pathsDicts);
    }

    /**
     * Adds dicts.
     *
     * ```php
     *  [ 'ru' =>
     *    [
     *     'path/lang/ru/lang.php',
     *     'path/lang/ru/validate.php',
     *    ]
     *  ]
     * ```
     *
     * @param array $dicts
     * @throws \Exception
     * @throws i18nException
     */
    public function addDicts(array $dicts)
    {
        if (!empty(static::$data)){
            return;
        }
        foreach ($dicts as $lang => $paths) {
            $total = [];
            foreach ($paths as $path) {
                $path = Alias::getAlias($path);
                if (!file_exists($path) || (!$data = require($path)) || !is_array($data)) {
                    throw new i18nException(i18nException::UNKNOWN_FILE, ['path' => $path]);
                    break 2;
                }
                $context         = basename($path, '.php');
                $total[$context] = array_merge(Helper::getValue($total[$context], [], true), $data);
            }
            static::$data[$lang] = array_merge(Helper::getValue(static::$data[$lang], [], true), $total);
        }
    }

    /**
     * Translate
     *
     * ```php
     * i18n::translate('bar.foo');
     * i18n::translate(['bar', 'foo']);
     * ```
     *
     * @param string|array $keys    chain keys
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
     * Translate
     *
     * @param string|array  $keys chain keys
     * @param array $placeholders
     * @param string|null  $category
     * @param string $locale
     * @return null|string
     */
    public static function t($keys, array $placeholders = [], $category = null, $locale = null)
    {
        /** @var self $i18n */
        $i18n = Container::load('i18n');
        return $i18n
            ->locale($locale ? : $i18n->locale)
            ->category($category ? : $i18n->category)
            ->removeBraces(true)
            ->translate($keys, $placeholders);
    }

    /**
     * Set locale.
     * @param string $locale
     * @return $this
     */
    public function locale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Set category.
     * @param string $category
     * @return $this
     */
    public function category($category)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * Removes braces in i18n-record.
     * @param bool $removeBraces
     * @return $this
     */
    public function removeBraces($removeBraces = false)
    {
        $this->removeBraces = $removeBraces;
        return $this;
    }

    /**
     * Get all i18-records by locale.
     *
     * @param array $only
     * @param array $exclude
     * @return array
     */
    public function getAll(array $only = [], array $exclude = [])
    {
        if (!isset(static::$data[$this->locale])) {
            return [];
        }
        return ArrayHelper::only(static::$data[$this->locale], $only, $exclude);
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

    public function addMulti(array $data)
    {
        static::$data = $data;
    }

    /**
     * Exists record.
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
     * Removes a record.
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

    /**
     * Clear records.
     */
    public function clear()
    {
        static::$data = [];
    }

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