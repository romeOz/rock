<?php

namespace rock\cookie;

use rock\exception\BaseException;

class CookieException extends BaseException
{
    const INVALID_SET = 'Cookie does not set.';
}