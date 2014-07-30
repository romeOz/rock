<?php
namespace rock\validation\rules;

class Consonant extends AbstractRegexRule
{
    protected function getPregFormat()
    {
        return '/^(\s|[b-df-hj-np-tv-zB-DF-HJ-NP-TV-Z])*$/';
    }
}

