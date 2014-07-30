<?php
namespace rock\validation\rules;

class Cntrl extends AbstractCtypeRule
{
    protected function ctypeFunction($input)
    {
        return ctype_cntrl($input);
    }
}

