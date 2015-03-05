<?php

namespace rock\cookie;

use rock\base\BaseException;

class CookieException extends BaseException
{
    const INVALID_SET = 'Cookie does not set.';
}