<?php

namespace rock\helpers;


use rock\base\BaseException;

class SerializeException extends BaseException
{
    const NOT_SERIALIZE = 'Value does not serialization.';
}