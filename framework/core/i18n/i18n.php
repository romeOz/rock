<?php
namespace rock\i18n;


use rock\base\CollectionInterface;
use rock\base\ComponentsTrait;
use rock\base\ObjectTrait;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\helpers\String;
use rock\Rock;

class i18n implements \ArrayAccess, CollectionInterface, i18nInterface
{
    use ComponentsTrait;


    protected static $data = [];


    /**
     * @var array
     */
    public $pathsDicts = [];

    public static $aliases = [
        self::UK => self::EN,
        self::EN_US => self::EN,
        self::RU_RU => self::RU,
    ];


    public function init()
    {
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
                    throw new Exception(Exception::CRITICAL, Exception::UNKNOWN_FILE, ['path' => $path]);
                    break 2;
                }
                $context         = basename($path, '.inc');
                $total[$context] = array_merge(Helper::getValueIsset($total[$context], []), $data);
            }
            static::$data[$lang] = array_merge(Helper::getValueIsset(static::$data[$lang], []), $total);
        }
    }

    /**
     * Get string by lang
     *
     * @param string|array $keys        - chain keys
     * @param array        $dataReplace - array replace
     * @param string|null  $language        - language
     * @param string|null  $category
     * @return null|string
     */
    public function get($keys, array $dataReplace = [], $category = null, $language = null)
    {
        if (!$result = $this->prepare($keys, $dataReplace, $category, $language)) {
            return null;
        }
        return $result;
    }

    /**
     * Get lang
     * ~~~~~~~~~~~~
     * get('en.foo')
     * get (array('en', 'foo'))
     * ~~~~~~~~~~~~
     * @param string|array $keys        - chain keys
     * @return mixed
     */
    public function __get($keys)
    {
        return $this->get($keys);
    }

    /**
     * Get lang
     * ~~~~~~~~~~~~
     * get('en.foo')
     * get (array('en', 'foo'))
     * ~~~~~~~~~~~~
     * @param string|array $keys        - chain keys
     * @return mixed
     */
    public function offsetGet($keys)
    {
        return ArrayHelper::getValue(static::$data, $keys);
    }

    /**
     * @param array $names
     * @return mixed
     */
    public function getMulti(array $names)
    {
        $result = [];
        foreach ($names as $name) {
            $result[$name] = $this->get($name);
        }
    }


    /**
     * @param array $only
     * @param array $exclude
     * @param null  $language
     * @return \ArrayIterator an iterator for traversing the langs in the collection.
     */
    public function getIterator(array $only = [], array $exclude = [], $language = null)
    {
        return new \ArrayIterator($this->getAll($only, $exclude, $language));
    }


    /**
     * @inheritdoc
     */
    public function getAll(array $only = [], array $exclude = [], $language = null)
    {
        if (empty($language)) {
            $language = $this->Rock->language;
        }
        return ArrayHelper::prepareArray(static::$data[$language], $only, $exclude);
    }

    /**
     * @inheritdoc
     */
    public function count($language = null)
    {
        return $this->getCount($language);
    }

    /**
     * @inheritdoc
     */
    public function getCount($language = null)
    {
        return isset($language) ? count(static::$data[$language]) : count(static::$data);
    }

    public function offsetExists($name)
    {
        return $this->has($name);
    }

    /**
     * Exists lang
     * @param string|array $keys        - chain keys
     * @return bool
     */
    public function has($keys)
    {
        return (bool)$this->get($keys);
    }

    /**
     * Add lang
     * ~~~~~~~~~~~~
     * add('en.foo', 'foo')
     * ~~~~~~~~~~~~
     *
     * @param string|array $keys        - chain keys
     * @param mixed  $value
     */
    public function add($keys, $value)
    {
        ArrayHelper::setValue(static::$data, !is_array($keys) ? explode('.', $keys) : $keys, $value);
    }

    /**
     * @param string|array $keys
     * @param mixed $value
     */
    public function __set($keys, $value)
    {
        $this->add($keys, $value);
    }

    /**
     * Add lang
     * @param string|array $keys        - chain keys
     * @param mixed  $value
     */
    public function offsetSet($keys, $value)
    {
        $this->add($keys, $value);
    }

    /**
     * @param array $data
     */
    public function addMulti(array $data)
    {
        foreach ($data as $name => $value) {
            $this->add($name, $value);
        }
    }

    /**
     * Removes a data.
     * ~~~~~~~~~~~~~
     * $array = ['foo' => 'test', 'bar'=> ['bar'=> 'bar', 'foo'=> 'foo']]
     * remove(['bar', 'foo']) => ['foo' => 'test', 'bar'=> ['bar'=> 'bar']]
     * remove(['bar']) => ['foo' => 'test']
     * ~~~~~~~~~~~~~
     *
     * @param string|array $keys - chain keys
     */
    public function remove($keys)
    {
        ArrayHelper::removeValue(static::$data, $keys);
    }

    /**
     * Removes the named lang.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `unset($collection[$name])`.
     * This is equivalent to [[remove()]].
     *
     * @param string $name the lang name
     */
    public function offsetUnset($name)
    {
        $this->remove($name);
    }


    /**
     * @param array $names
     */
    public function removeMulti(array $names)
    {
        foreach ($names as $name) {
            $this->remove($name);
        }
    }

    public function removeAll()
    {
        static::$data = [];
    }

    /**
     * Prepare lang
     *
     * @param string|array $keys - key of array
     * @param array        $dataReplace
     * @param string|null  $language - language
     * @param string|null  $category
     * @throws Exception
     * @return mixed|null
     */
    protected function prepare($keys, array $dataReplace = [], $category = null, $language = null)
    {
        if (empty($language)) {
            $language = $this->Rock->language;
        }
        if (empty($category)) {
            $category = 'lang';
        }
        $result = ArrayHelper::getValue(static::$data[$language][$category], $keys);
        if (!isset($result)) {

            $keys = is_array($keys) ? implode('][', $keys) : $keys;
            throw new Exception(
                Exception::ERROR, Exception::UNKNOWN_I18N, ['name' => "{$category}[{$keys}]"]
            );

        }

        return String::replace($result, $dataReplace);
    }

    public static function getLanguage($language = null)
    {
        if (!isset($language)) {
            $language = Rock::$app->language;
        }

        if (isset(static::$aliases[$language])) {
            return static::$aliases[$language];
        }

        return $language;
    }

//    /**
//     * Create json file (eg. for js lang)
//     *
//     * @param string $pathname - pathname of file
//     * @param array  $data     - data
//     * @param bool   $notRepeatCreate
//     */
//    public static function createJSON($pathname, array $data, $notRepeatCreate = true)
//    {
//        if ($notRepeatCreate === true
//            && file_exists($pathname)
//        ) {
//            return;
//        }
//        File::create(
//            $pathname,
//            json_encode(
//                $data,
//                JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
//            ),
//            LOCK_EX,
//            false
//        );
//    }
}
