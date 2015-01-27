<?php
namespace rock\file;

use League\Flysystem\AdapterInterface;
use League\Flysystem\CacheInterface;
use League\Flysystem\Filesystem;
use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\helpers\ArrayHelper;
use rock\helpers\StringHelper;

class FileManager extends Filesystem implements ObjectInterface
{
    use ObjectTrait {
        ObjectTrait::__construct as parentConstruct;
        ObjectTrait::__call as parentCall;
    }

    const TYPE_FILE = 'file';
    const TYPE_DIR = 'dir';
    const META_TIMESTAMP = 'timestamp';
    const META_MIMETYPE = 'mimetype';

    protected $errors = [];

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        $this->parentConstruct($config);
        if ($this->cache instanceof CacheInterface) {
            $this->cache->save();
        }
        parent::__construct($this->adapter, $this->cache, $this->config);
    }

    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function setConfig($config = null)
    {
        $this->config = $config;
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
        if (StringHelper::isRegexp($path) && (!$path = $this->searchByPattern($path))) {
            return false;
        }

        try {
            return parent::read($path);
        } catch (\Exception $e) {
            $this->errors[] = StringHelper::replace(FileException::UNKNOWN_FILE, ['path' => $path]);
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
        if (StringHelper::isRegexp($path) && (!$path = $this->searchByPattern($path))) {
            return false;
        }

        try {
            return parent::readAndDelete($path);
        } catch (\Exception $e) {
            $this->errors[] = StringHelper::replace(FileException::UNKNOWN_FILE, ['path' => $path]);
        }
        return false;
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
        if (StringHelper::isRegexp($path) && (!$path = $this->searchByPattern($path, false, $is))) {
            return false;
        }

        return isset($is)
            ? parent::has($path) && parent::getMetadata($path)['type'] === $is
            : parent::has($path);
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
            return parent::write($path, $contents, $config);
        } catch (\Exception $e) {
            $this->errors[] = StringHelper::replace(FileException::FILE_EXISTS, ['path' => $path]);
        }
        return false;
    }

    public function writeStream($path, $resource, $config = null)
    {
        try {
            return parent::writeStream($path, $resource, $config);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function update($path, $contents, $config = null)
    {
        try {
            return parent::update($path, $contents, $config);
        } catch (\Exception $e) {
            $this->errors[] = StringHelper::replace(FileException::FILE_EXISTS, ['path' => $path]);
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function updateStream($path, $resource, $config = null)
    {
        try {
            return parent::updateStream($path, $resource, $config);
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
        if (StringHelper::isRegexp($path) && (!$path = $this->searchByPattern($path))) {
            return false;
        }

        try {
            return parent::delete($path);
        } catch (\Exception $e) {
            $this->errors[] = StringHelper::replace(FileException::UNKNOWN_FILE, ['path' => $path]);
        }
        return false;
    }

    /**
     * Clear current dir
     */
    public function deleteAll()
    {
        foreach (parent::listContents() as $value) {
            if (!isset($value['type']) || $value['type'] === self::TYPE_DIR) {
                parent::deleteDir($value['path']);
                continue;
            }

            parent::delete($value['path']);
        }
    }

    /**
     * @inheritdoc
     */
    public function rename($path, $newpath)
    {
        try {
            return parent::rename($path, $newpath);
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
            $metadata = parent::getWithMetadata($path, ['timestamp','mimetype']);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }

        return $this->rename($path, StringHelper::replace($newpath, array_merge($metadata, $dataReplace)));
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
            return parent::copy($path, $newpath);
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
        if (StringHelper::isRegexp($path) && (!$path = $this->searchByPattern($path))) {
            return false;
        }

        try {
            return parent::getMetadata($path);
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
     * @return  array|false   metadata
     */
    public function getWithMetadata($path, array $metadata)
    {
        if (StringHelper::isRegexp($path) && (!$path = $this->searchByPattern($path))) {
            return false;
        }

        try {
            $result = parent::getWithMetadata($path, $metadata);
            if (!array_filter($result)) {
                return false;
            }
            return $result;
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
        if (StringHelper::isRegexp($path) && (!$path = $this->searchByPattern($path))) {
            return false;
        }

        try {
            return parent::getVisibility($path);
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
        if (StringHelper::isRegexp($path) && (!$path = $this->searchByPattern($path))) {
            return false;
        }

        try {
            return parent::getTimestamp($path);
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
        if (StringHelper::isRegexp($path) && (!$path = $this->searchByPattern($path))) {
            return false;
        }

        try {
            return parent::getMimetype($path);
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
        if (StringHelper::isRegexp($path) && (!$path = $this->searchByPattern($path))) {
            return false;
        }

        try {
            return parent::getSize($path);
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return false;
    }

    /**
     * List the filesystem contents.
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
            if (StringHelper::isRegexp($directory)) {
                return $this->searchDirByPattern($directory, $recursive, $is);
            }
        }

        $result = parent::listContents($directory, $recursive);

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
     * List all paths.
     *
     * @param string $directory
     * @param bool   $recursive
     * @param null   $is
     * @return  array  paths
     */
    public function listPaths($directory = '', $recursive = false, $is = null)
    {
        if (!empty($directory)) {
            if (StringHelper::isRegexp($directory)) {
                return ArrayHelper::getColumn($this->searchDirByPattern($directory, $recursive, $is), 'path');
            }
        }

        if (!isset($is)) {
            return parent::listPaths($directory, $recursive);
        }
        $result = [];
        foreach (parent::listContents($directory, $recursive) as $value) {
            if ($value['type'] !== $is) {
                continue;
            }

            $result[] = $value['path'];
        }
        return $result;
    }

    /**
     * List contents with metadata.
     *
     * @param array  $keys metadata keys
     * @param string $directory
     * @param bool   $recursive
     * @param null   $is
     * @return  array            listing with metadata
     */
    public function listWith(array $keys = [], $directory = '', $recursive = false, $is = null)
    {
        if (!empty($directory)) {
            if (StringHelper::isRegexp($directory)) {
                return $this->searchFilesWithByPattern($keys, $directory, $recursive, $is = null);
            }
        }

        $result = parent::listWith($keys, $directory, $recursive);
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
     * @inheritdoc
     */
    public function __call($method, array $arguments)
    {
        return parent::__call($method, $arguments);
    }

    protected function searchByPattern($pattern, $error = true, $is = self::TYPE_FILE)
    {
        foreach (parent::listContents('', true) as $data) {
            if (isset($is) && $data['type'] !== $is) {
                continue;
            }

            if (preg_match($pattern, $data['path'])) {
                return $data['path'];
            }
        }
        if ($error === true) {
            $this->errors[] = StringHelper::replace(FileException::UNKNOWN_FILE, ['path' => $pattern]);
        }
        return null;
    }

    protected function searchDirByPattern($pattern, $recursive = false, $is = null)
    {
        $result =[];
        foreach (parent::listContents('', $recursive) as $data) {
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
        foreach (parent::listWith($keys, '', $recursive) as $data) {
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


