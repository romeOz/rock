<?php
namespace rock\di;

use rock\base\BaseException;

class ContainerException extends BaseException
{
    const ARGS_NOT_ARRAY = 'Object configuration must be an array containing a "class" element.';
    const INVALID_CONFIG = 'Configuration must be an array or \Closure.';
}