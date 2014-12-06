<?php
namespace rock\cache;

use rock\exception\BaseException;

class CacheException extends BaseException
{
    const NOT_UNIQUE  = 'Keys must be unique: {data}.';
    const INVALID_SAVE = 'Cache invalid save by key: {key}.';
}