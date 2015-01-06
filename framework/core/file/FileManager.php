<?php
namespace rock\file;

use League\Flysystem\AdapterInterface;
use League\Flysystem\CacheInterface;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\PluginInterface;
use rock\base\ComponentsTrait;
use rock\helpers\ArrayHelper;
use rock\helpers\String;

/**
 * @method bool deleteDir(string $dirname)
 * @method bool put(string $path, $contents, $config = null)
 * @method bool createDir(string $dirname)
 * @method bool putStream(string $path, $resource, $config = null)
 * @method false|resource readStream($path)
 * @method flushCache();
 * @method addPlugin(PluginInterface $plugin)
 * @method AdapterInterface getAdapter()
 * @method Config getConfig()
 * @method CacheInterface getCache()
 */
class FileManager
{
    use ComponentsTrait {
        ComponentsTrait::__call as parentCall;
    }

    const TYPE_FILE = 'file';
    const TYPE_DIR = 'dir';
    const META_TIMESTAMP = 'timestamp';
    const META_MIMETYPE = 'mimetype';
    const VISIBILITY_PRIVATE = Filesystem::VISIBILITY_PRIVATE;
    const VISIBILITY_PUBLIC = Filesystem::VISIBILITY_PUBLIC;

    /** @var  \Closure|AdapterInterface */
    public $adapter;
    /** @var  \Closure|CacheInterface|null */
    public $cache;

    public $config;
    /** @var  Filesystem */
    protected $filesystem;

    protected $errors = [];


    /**
     * @return Filesystem
     */
    protected function getFilesystem()
    {
        if (!isset($this->filesystem)) {
            if ($this->adapter instanceof \Closure) {
                $this->adapter = call_user_func($this->adapter, $this);
            }

            if ($this->cache instanceof \Closure) {
                $this->cache = call_user_func($this->cache, $this);
            }

            if ($this->cache instanceof CacheInterface) {
                $this->cache->save();
            }
            $this->filesystem = new Filesystem($this->adapter, $this->cache, $this->config);
        }

        return $this->filesystem;
    }

    /**
     * Check whether a path exists
     *
     * ```php
     * has('cache/file.tmp')
     * has('~/file.tmp$/')
     * ```
     *
     * @param  string $path path to check or regexp pattern
     * @param null    $is
     * @return boolean whether the path exists
     */
    public function has($path, $is = null)
    {
        if (String::isRegexp($path) && (!$path = $this->searchByPattern($path, false, $is))) {
            return false;
        }

        return isset($is)
            ? $this->getFilesystem()->has($path) && $this->getFilesystem()->getMetadata($path)['type'] === $is
            : $this->getFilesystem()->has($path);
    }

    /**
     * Read a file
     *
     * ```php
     * read('cache/file.tmp')
     * read('~/file.tmp$/')
     * ```
     *
     * @param  string $path path to file or regexp pattern
     * @return string|false          file contents or FALSE when fails
     *                               to read existing file
     */
    public function read($path)
    {
        if (String::isRegexp($path) && (!$path = $this->searchByPattern($path))) {
            return false;
        }

        try {
            return $this->getFilesystem()->read($path);
        } catch (\Exception $e) {
            $this->errors[] = String::replace(FileException::UNKNOWN_FILE, ['path' => $path]);
        }
        return false;
    }

    /**
     * Read and delete a file.
     *
     * @param   string  $path
     * @return  string|false  file contents
     */
    public function readAndDelete($path)
    {
        if (String::isRegexp($path) && (!$path = $this->searchByPattern($path))) {
            return false;
        }

        try {
            return $this->getFilesystem()->readAndDelete($path);
        } catch (\Exception $e) {
            $this->errors[] = String::replace(FileException::UNKNOWN_FILE, ['path' => $path]);
        }
        return false;
    }

    /**
     * Write a file
     *
     * @param  string              $path     path to file
     * @param  string              $contents file contents
     * @param  mixed               $config
     * @return boolean             success boolean
     */
    public function write($path, $contents, $config = null)
    {
        try {
            return $this->getFilesystem()->write($path, $contents, $config);
        } catch (\Exception $e) {
            $this->errors[] = String::replace(FileException::FILE_EXISTS, ['path' => $path]);
        }
        return false;
    }

    public function writeStream($path, $resource, $config = null)
    {
        try {
            return $this->getFilesystem()->writeStream($path, $resource, $config);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return false;
    }

    /**
     * Update a file
     *
     * @param  string                $path     path to file
     * @param  string                $contents file contents
     * @param   mixed                $config   Config object or visibility setting
     * @return boolean               success boolean
     */
    public function update($path, $contents, $config = null)
    {
        try {
            return $this->getFilesystem()->update($path, $contents, $config);
        } catch (\Exception $e) {
            $this->errors[] = String::replace(FileException::FILE_EXISTS, ['path' => $path]);
        }
        return false;
    }

    public function updateStream($path, $resource, $config = null)
    {
        try {
            return $this->getFilesystem()->updateStream($path, $resource, $config);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return false;
    }

    /**
     * Rename a file
     *
     * @param  string                $path    path to file
     * @param  string                $newpath new path
     * @return boolean               success boolean
     */
    public function rename($path, $newpath)
    {
        try {
            return $this->getFilesystem()->rename($path, $newpath);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return false;
    }

    /**
     * Rename file/dir by Mask
     * @param       $path
     * @param       $newpath
     * @param array $dataReplace
     *
     * ```php
     * renameByMask('test', 'test_{num}', ['num' => 2]);
     * // result: test_2
     * ```
     * @return bool
     */
    public function renameByMask($path, $newpath, array $dataReplace = [])
    {
        try {
            $metadata = $this->getFilesystem()->getWithMetadata($path, ['timestamp','mimetype']);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }

        return $this->rename($path, String::replace($newpath, array_merge($metadata, $dataReplace)));
    }

    /**
     * Copy a file
     *
     * @param   string  $path
     * @param   string  $newpath
     * @return  boolean
     */
    public function copy($path, $newpath)
    {
        try {
            return $this->getFilesystem()->copy($path, $newpath);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return false;
    }

    /**
     * Get a file's metadata
     *
     * ```php
     * getMetadata('cache/file.tmp')
     * getMetadata('~/file.tmp$/')
     * ```
     *
     * @param  string $path path to file or regexp pattern
     * @return array|false           file metadata or FALSE when fails
     *                               to fetch it from existing file
     */
    public function getMetadata($path)
    {
        if (String::isRegexp($path) && (!$path = $this->searchByPattern($path))) {
            return false;
        }

        try {
            return $this->getFilesystem()->getMetadata($path);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return false;
    }

    /**
     * Get metadata for an object with required metadata
     *
     * ```php
     * getWithMetadata('cache/file.tmp')
     * getWithMetadata('~/file.tmp$/')
     * ```
     *
     * @param  string $path path to file or regexp pattern
     * @param   array   $metadata  metadata keys
     * @return  array   metadata
     */
    public function getWithMetadata($path, array $metadata)
    {
        if (String::isRegexp($path) && (!$path = $this->searchByPattern($path))) {
            return false;
        }

        try {
            return $this->getFilesystem()->getWithMetadata($path, $metadata);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return false;
    }

    /**
     * Get a file's visibility
     *
     * ```php
     * getVisibility('cache/file.tmp')
     * getVisibility('~/file.tmp$/')
     * ```
     *
     * @param  string $path path to file or regexp pattern
     * @return  string|false  visibility (public|private) or FALSE
     *                        when fails to check it in existing file
     */
    public function getVisibility($path)
    {
        if (String::isRegexp($path) && (!$path = $this->searchByPattern($path))) {
            return false;
        }

        try {
            return $this->getFilesystem()->getVisibility($path);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return false;
    }

    /**
     * Get a file's timestamp
     *
     * ```php
     * getTimestamp('cache/file.tmp')
     * getTimestamp('~/file.tmp$/')
     * ```
     *
     * @param  string $path path to file or regexp pattern
     * @return string|false timestamp or FALSE when fails
     *                      to fetch timestamp from existing file
     */
    public function getTimestamp($path)
    {
        if (String::isRegexp($path) && (!$path = $this->searchByPattern($path))) {
            return false;
        }

        try {
            return $this->getFilesystem()->getTimestamp($path);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return false;
    }

    /**
     * Get a file's mimetype
     *
     * ```php
     * getMimetype('cache/file.tmp')
     * getMimetype('~/file.tmp$/')
     * ```
     *
     * @param  string $path path to file or regexp pattern
     * @return string|false file mimetype or FALSE when fails
     *                      to fetch mimetype from existing file
     */
    public function getMimetype($path)
    {
        if (String::isRegexp($path) && (!$path = $this->searchByPattern($path))) {
            return false;
        }

        try {
            return $this->getFilesystem()->getMimetype($path);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return false;
    }

    /**
     * Get a file's size
     *
     * ```php
     * getSize('cache/file.tmp')
     * getSize('~/file.tmp$/')
     * ```
     *
     * @param  string $path path to file or regexp pattern
     * @return  int|false     file size or FALSE when fails
     *                        to check size of existing file
     */
    public function getSize($path)
    {
        if (String::isRegexp($path) && (!$path = $this->searchByPattern($path))) {
            return false;
        }

        try {
            return $this->getFilesystem()->getSize($path);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return false;
    }

    /**
     * Delete a file
     *
     * ```php
     * delete('cache/file.tmp')
     * delete('~/file.tmp$/')
     * ```
     *
     * @param  string $path path to file or regexp pattern
     * @return boolean               success boolean
     */
    public function delete($path)
    {
        if (String::isRegexp($path) && (!$path = $this->searchByPattern($path))) {
            return false;
        }

        try {
            return $this->getFilesystem()->delete($path);
        } catch (\Exception $e) {
            $this->errors[] = String::replace(FileException::UNKNOWN_FILE, ['path' => $path]);
        }
        return false;
    }


    /**
     * Clear current dir
     */
    public function deleteAll()
    {
        foreach ($this->getFilesystem()->listContents() as $value) {
            if (!isset($value['type']) || $value['type'] === self::TYPE_DIR) {
                $this->getFilesystem()->deleteDir($value['path']);
                continue;
            }

            $this->getFilesystem()->delete($value['path']);
        }
    }

    /**
     * List the filesystem contents
     *
     * ```php
     * listContents('folder/foo')
     * listContents('~/foo$/')
     * ```
     *
     * @param  string  $directory
     * @param boolean $recursive
     * @param null     $is
     * @return array    contents
     */
    public function listContents($directory = '', $recursive = false, $is = null)
    {
        if (!empty($directory)) {
            if (String::isRegexp($directory)) {
                return $this->searchDirByPattern($directory, $recursive, $is);
            }
        }

        $result = $this->getFilesystem()->listContents($directory, $recursive);

        return isset($is)
            ? array_filter(
                $result,
                function($value) use ($is){
                    return $value['type'] === $is;
                }
            )
            : $result;
    }

    /**
     * List all paths
     *
     * @param string $directory
     * @param bool   $recursive
     * @param null   $is
     * @return  array  paths
     */
    public function listPaths($directory = '', $recursive = false, $is = null)
    {
        if (!empty($directory)) {
            if (String::isRegexp($directory)) {
                return ArrayHelper::getColumn($this->searchDirByPattern($directory, $recursive, $is), 'path');
            }
        }

        if (!isset($is)) {
            return $this->getFilesystem()->listPaths($directory, $recursive);
        }
        $result = [];
        foreach ($this->getFilesystem()->listContents($directory, $recursive) as $value) {
            if ($value['type'] !== $is) {
                continue;
            }

            $result[] = $value['path'];
        }
        return $result;
    }

    /**
     * List contents with metadata
     *
     * @param array  $keys - metadata keys
     * @param string $directory
     * @param bool   $recursive
     * @param null   $is
     * @return  array            listing with metadata
     */
    public function listWith(array $keys = [], $directory = '', $recursive = false, $is = null)
    {
        if (!empty($directory)) {
            if (String::isRegexp($directory)) {
                return $this->searchFilesWithByPattern($keys, $directory, $recursive, $is = null);
            }
        }

        $result = $this->getFilesystem()->listWith($keys, $directory, $recursive);
        return isset($is)
            ? array_filter(
                $result,
                function($value) use ($is){
                    return $value['type'] === $is;
                }
            )
            : $result;
    }

    /**
     * @return string[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param $name - name
     * @param $params
     * @return mixed
     */
    public function __call($name, $params)
    {
        return call_user_func_array([$this->getFilesystem(), $name], $params);
    }


    protected function searchByPattern($pattern, $error = true, $is = self::TYPE_FILE)
    {
        foreach ($this->getFilesystem()->listContents('', true) as $data) {
            if (isset($is) && $data['type'] !== $is) {
                continue;
            }

            if (preg_match($pattern, $data['path'])) {
                return $data['path'];
            }
        }
        if ($error === true) {
            $this->errors[] = String::replace(FileException::UNKNOWN_FILE, ['path' => $pattern]);
        }
        return null;
    }

    protected function searchDirByPattern($pattern, $recursive = false, $is = null)
    {
        $result =[];
        foreach ($this->getFilesystem()->listContents('', $recursive) as $data) {
            if (isset($is) && isset($data['type']) && $data['type'] !== $is) {
                continue;
            }
            if (preg_match($pattern, $data['path'])) {
                $result[] = $data;
            }
        }

        return $result;
    }


    protected function searchFilesWithByPattern(array $keys = [], $pattern, $recursive = false, $is = null)
    {
        $result =[];
        foreach ($this->getFilesystem()->listWith($keys, '', $recursive) as $data) {
            if (isset($is) && $data['type'] !== $is) {
                continue;
            }
            if (preg_match($pattern, $data['path'])) {
                $result[] = $data;
            }
        }

        return $result;
    }
}


