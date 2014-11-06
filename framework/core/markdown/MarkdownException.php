<?php

namespace rock\markdown;


use rock\exception\BaseException;

class MarkdownException extends BaseException
{
    const UNKNOWN_HOSTING = 'Unknown hosting: {name}.';
}