<?php
namespace rock\validation\rules;

class Xdigit extends AbstractCtypeRule
{
    public function ctypeFunction($input)
    {
        return ctype_xdigit($input);
    }
}

