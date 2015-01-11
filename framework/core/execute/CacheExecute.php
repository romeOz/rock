<?php

namespace rock\execute;


use rock\helpers\FileHelper;
use rock\Rock;

class CacheExecute extends Execute
{
    public $path = '@common/runtime/execute';

    /**
     * Create file
     *
     * @param string $path
     * @param string $value
     * @return bool
     */
    protected function createFile($path, $value)
    {
        return FileHelper::create($path, "<?php\n" . $value, LOCK_EX);
    }

    /**
     * @param string $value
     * @return string
     */
    protected function preparePath($value)
    {
        return Rock::getAlias($this->path) . DIRECTORY_SEPARATOR . md5($value) . '.php';
    }


    /**
     * Get
     *
     * @param string $value - key
     * @param array  $data
     * @param array  $params
     * @throws ExecuteException
     * @return mixed
     */
    public function get($value, array $params = null, array $data = null)
    {
        $path = static::preparePath($value);

        if (!file_exists($path) && !$this->createFile($path, $value)) {
            throw new ExecuteException(ExecuteException::NOT_CREATE_FILE, ['path' => $path]);
        }
        unset($value);

        return include($path);
    }
} 