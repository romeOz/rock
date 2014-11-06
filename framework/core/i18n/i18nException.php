<?php

namespace rock\i18n;


use rock\exception\BaseException;

class i18nException extends BaseException
{
    const UNKNOWN_I18N = 'Unknown i18n: {name}.';
}