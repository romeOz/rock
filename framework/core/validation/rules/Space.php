<?php
namespace rock\validation\rules;

class Space extends AbstractCtypeRule
{
    protected function ctypeFunction($input)
    {
        return ctype_space($input);
    }
}

