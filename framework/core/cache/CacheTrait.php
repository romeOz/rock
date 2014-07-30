<?php

namespace rock\cache;


use rock\helpers\Serialize;

trait CacheTrait
{
    use CommonTrait;

    /**
     * @inheritdoc
     */
    public function removeMulti(array $keys)
    {
        /** @var $this CacheInterface */

        foreach ($keys as $key) {
            $this->remove($key);
        }
    }

    protected function serialize($value)
    {
        if (!is_array($value)) {
            return $value;
        }
        return Serialize::serialize($value, $this->serializer);
    }

    protected function provideGet($key)
    {
        if ($this->enabled === false || empty($key)) {
            return false;
        }

        $key = $this->prepareKey($key);
        if (($result = static::$storage->get($key)) === false) {
            if (($result = $this->getLock($key)) === false) {
                return false;
            }
        }

        return $result;
    }
}