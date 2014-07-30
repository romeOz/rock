<?php
namespace rock\validation\rules;

class Vowel extends AbstractRegexRule
{
    protected function getPregFormat()
    {
        return '/^(\s|[aeiouAEIOU])*$/';
    }
}

