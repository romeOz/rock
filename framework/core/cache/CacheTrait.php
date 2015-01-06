<?php

namespace rock\cache;


use rock\helpers\Serialize;

trait CacheTrait
{
    use CommonTrait;

    /**
     * Pessimistic locking.
     * @var bool
     */
    public $lock = true;

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
        if (empty($key)) {
            return false;
        }

        $key = $this->prepareKey($key);
        if (($result = $this->storage->get($key)) === false) {
            if ($this->lock === false || ($result = $this->getLock($key)) === false) {
                return false;
            }
        }

        return $result;
    }
}