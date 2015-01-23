<?php

namespace rock\template;


use rock\base\BaseException;

class TemplateException extends BaseException
{
    const UNKNOWN_SNIPPET = 'Unknown snippet: {name}.';
    const UNKNOWN_FILTER = 'Unknown filter: {name}.';
    const UNKNOWN_PARAM_FILTER = 'Unknown param filter: {filter}.';
} 