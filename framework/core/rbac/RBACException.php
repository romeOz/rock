<?php

namespace rock\rbac;


use rock\base\BaseException;

class RBACException extends BaseException
{
    const UNKNOWN_CHILD = 'Unknown child: {name}.';
    const UNKNOWN_TYPE = 'Unknown type: {name}.';
    const UNKNOWN_ROLE = 'Unknown role: {name}.';
    const UNKNOWN_PERMISSION = 'Unknown permission: {name}.';
    const NOT_DATA_PARAMS = 'Does not data params of item.';
}