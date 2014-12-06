<?php
namespace rock\cache;

use rock\file\FileManager;
use rock\helpers\ArrayHelper;
use rock\helpers\Trace;
use rock\Rock;

class CacheFile implements CacheInterface
{
    use CommonTrait {
        CommonTrait::prepareTags as parentPrepareTags;
    }

    /**
     * Max files in folder
     * @var int
     */
    public $maxFiles = 100;

    /**
     * Extension of cache file
     * @var string
     */
    public $extensionFileCache = 'tmp';

    /**
     * Path to file of cache
     *
     * @var string
     */
    protected $pathFileCache;

    /** @var FileManager|\Closure */
    public $adapter;


    /**
     * @return FileManager
     * @throws CacheException
     */
    public function getAdapter()
    {
        if ($this->adapter instanceof FileManager) {
            return $this->adapter;
        }
        if ($this->adapter instanceof \Closure) {
            $this->adapter = call_user_func($this->adapter, $this);
        } else {
            $this->adapter = Rock::factory($this->adapter);
        }
        if (!$this->adapter instanceof FileManager) {
            throw new CacheException(CacheException::UNKNOWN_CLASS, ['class' => $this->adapter]);
        }

        return $this->adapter;
    }
        
    /**
     * @inheritdoc
     */
    public function getStorage()
    {
        throw new CacheException(CacheException::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        if (empty($key)) {
            return false;
        }

        $key = $this->prepareKey($key);
        if (($result = $this->provideGet($key, $file)) === false) {
            return false;
        }

        Rock::trace(
            Trace::CACHE_GET,
            [
                'class' => static::className(),
                'key' => $key,
                'path' => $file['path'] . DS . $key . '.' . $this->extensionFileCache,
                'value' => $result
            ]
        );

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value = null, $expire = 0, array $tags = null)
    {
        if (empty($key)) {
            return false;
        }
        $key = $this->prepareKey($key);

        $this->preparePaths($key, $this->prepareTags($tags));

        return $this->provideSet($value, $expire);
    }

    /**
     * @inheritdoc
     */
    public function add($key, $value = null, $expire = 0, array $tags = null)
    {
        if (empty($key)) {
            return false;
        }

        $this->preparePaths($this->prepareKey($key), $this->prepareTags($tags));
        if ($this->exists($key)) {
            return false;
        }

        return $this->provideSet($value, $expire);
    }

    /**
     * @inheritdoc
     */
    public function exists($key)
    {
        $key = $this->prepareKey($key);
        if ($this->provideGet($key, $file) === false) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function touch($key, $expire = 0)
    {
        $key = $this->prepareKey($key);
        if (($result = $this->provideGet($key, $file, $data)) === false) {
            return false;
        }
        $data['expire'] = $this->calculateExpire($expire);

        return $this->setContent($file['path'], $data);
    }

    /**
     * @inheritdoc
     */
    public function increment($key, $offset = 1, $expire = 0)
    {
        $hash = $this->prepareKey($key);

        if ($this->provideGet($hash, $file, $data) !== false) {
            $data['value'] = (int)$data['value'] + $offset;
            $data['expire'] = $this->calculateExpire($expire);
            if ($this->setContent($file['path'], $data) === false) {
                return false;
            }
            return $data['value'];
        }

        if ($this->set($key, $offset, $expire) === false) {
            return false;
        }
        return $offset;
    }

    /**
     * @inheritdoc
     */
    public function decrement($key, $offset = 1, $expire = 0)
    {
        $hash = $this->prepareKey($key);
        if ($this->provideGet($hash, $file, $data) !== false) {
            $data['expire'] = $this->calculateExpire($expire);
            $data['value'] = (int)$data['value'] - $offset;

            if ($this->setContent($file['path'], $data) === false) {
                return false;
            }
            return $data['value'];
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function remove($key)
    {
        $key = $this->prepareKey($key);
        return $this->getAdapter()->delete("~/{$key}.{$this->extensionFileCache}$/");
    }

    /**
     * @inheritdoc
     */
    public function removeMulti(array $keys)
    {
        foreach ($keys as $key) {
            $this->remove($key);
        }
    }

    /**
     * @inheritdoc
     */
    public function getTag($tag)
    {
        $tag = $this->prepareTag($tag);
        if (!$result = $this->getAdapter()->listContents("~/[^\/]*{$tag}[^\/]*/", true, FileManager::TYPE_FILE)) {
            return false;
        }

        return ArrayHelper::getColumn($result, 'filename');
    }

    /**
     * @inheritdoc
     */
    public function existsTag($tag)
    {
        $tag = $this->prepareTag($tag);
        return $this->getAdapter()->has("~/[^\/]*{$tag}[^\/]*$/", FileManager::TYPE_DIR);
    }

    /**
     * @inheritdoc
     */
    public function removeTag($tag)
    {
        $result = false;
        $tag = $this->prepareTag($tag);
        foreach ($this->getAdapter()->listContents("~/[^\/]*{$tag}[^\/]*$/", false, FileManager::TYPE_DIR) as $value) {
            $result = $this->getAdapter()->deleteDir($value['path']);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getAllKeys()
    {
        if (!$result = $this->getAdapter()->listContents("~/{$this->extensionFileCache}$/i", true, FileManager::TYPE_FILE)) {
            return null;
        }

        return ArrayHelper::getColumn($result, 'filename');
    }

    /**
     * @inheritdoc
     */
    public function getAll()
    {
        $result = $this->getAdapter()->listContents('', true, FileManager::TYPE_FILE);
        foreach ($result as $key => $value) {
            $result[$key]['value'] = $this->getAdapter()->read($value['path']);
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function flush()
    {
        $this->getAdapter()->deleteAll();
        return true;
    }

    /**
     * @inheritdoc
     */
    public function status()
    {
        throw new CacheException(CacheException::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @param string $key
     * @param string $tags - name of tag
     * @return void
     */
    protected function preparePaths($key, $tags = null)
    {
        $pathname = [];

        if (!empty($tags)) {
            $pathname[] = $tags;
        }
        /**
         * max files
         */
        $num = null;
        if (!empty($this->maxFiles)) {
            $num = floor(
                           count(
                    $this->getAdapter()
                        ->listContents(
                            !empty($tags)
                                ? $tags
                                : "~/^\\d+\//",
                            true,
                            FileManager::TYPE_FILE
                        )
                ) / $this->maxFiles);
        }

        if (isset($num)) {
            $pathname[] =$num;
        }
        $pathname[] = $key;
        $this->pathFileCache = implode(DIRECTORY_SEPARATOR, $pathname) . '.' . $this->extensionFileCache;
    }

    /**
     * @param array $tags - tags
     * @return string|null
     */
    protected function prepareTags(array $tags = null)
    {
        if (!$tags = $this->parentPrepareTags($tags)) {
            return null;
        }

        return implode('-', $tags);
    }

    /**
     * Get data file cache
     *
     * @param string|int $key - key
     * @throws CacheException
     * @return bool|mixed
     */
    protected function getDataFile($key)
    {
        if (!$metadata = $this->getAdapter()->getMetadata("~/{$key}.{$this->extensionFileCache}$/")) {
            return false;
        }

        return $metadata;
    }

    protected function provideSet($value, $expire)
    {
        return $this->getAdapter()->put(
            $this->pathFileCache,
            $this->serialize([
                                 'expire' => $this->calculateExpire($expire),
                                 'value' => $value
                             ])
        );
    }

    /**
     * @param string $key  - hash-key
     * @param array  $metadata - array data of file
     * @param null   $result
     * @return mixed
     */
    protected function provideGet($key, &$metadata = null, &$result = null)
    {
        if (!$metadata = $this->getDataFile($key)) {
            return false;
        }

        if (($result = $this->getContent($metadata['path'])) === false) {
            return false;
        }
        if ($this->validExpire(isset($result['expire']) ? $result['expire'] : 0) === false) {
            $this->getAdapter()->delete($metadata['path']);

            return false;
        }

        return isset($result['value']) ? $result['value'] : null;
    }

    protected function getContent($path)
    {
        if (!$this->getAdapter()->has($path, FileManager::TYPE_FILE)) {
            return false;
        }

        return $this->unserialize($this->getAdapter()->read($path));
    }

    protected function setContent($path, $value)
    {
        if (!$this->getAdapter()->has($path, FileManager::TYPE_FILE)) {
            return false;
        }

        return $this->getAdapter()->update($path, $this->serialize($value));
    }

    /**
     * Validity expire
     *
     * @param int $expire - expire
     * @return bool
     */
    protected function validExpire($expire)
    {
        $expire = (int)$expire;

        return $expire === 0 || $expire > time();
    }

    /**
     * @param int $expire
     * @return int
     */
    protected function calculateExpire($expire)
    {
        if (!empty($expire)) {
            return time() + $expire;
        }

        return 0;
    }
}