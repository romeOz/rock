<?php

namespace rock\helpers;


use rock\exception\BaseException;

class SerializeException extends BaseException
{
    const NOT_SERIALIZE = 'Value does not serialization.';
}