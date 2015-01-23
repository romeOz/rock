<?php

namespace rock\markdown;


use rock\base\BaseException;

class MarkdownException extends BaseException
{
    const UNKNOWN_HOSTING = 'Unknown hosting: {name}.';
}