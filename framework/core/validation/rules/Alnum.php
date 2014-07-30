<?php
namespace rock\validation\rules;

class Alnum extends AbstractCtypeRule
{
    protected function filter($input)
    {
        return $this->filterWhiteSpaceOption($input);
    }

    protected function ctypeFunction($input)
    {
        return ctype_alnum($input);
    }
}

