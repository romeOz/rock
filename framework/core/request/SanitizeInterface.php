<?php

namespace rock\request;


interface SanitizeInterface
{
    const STRIP_TAGS = 'stripTags';
    const STRIP_SCRIPT = 'stripScript';
    const NOISE_WORDS = 'noiseWords';
    const REMOVE_PUNCTUATION = 'removePunctuation';
    const URLENCODE = 'urlencode';
    const HTMLENCODE = 'htmlencode';
    const EMAIL = 'email';
    const NUMBERS = 'numbers';
    const POSITIVE = 'positive';
    const BASIC_TAGS = 'basicTags';
    const UNSERIALIZE = 'unserialize';
    const TO_TYPE = 'toType';

    const ANY = 'ANY';
} 