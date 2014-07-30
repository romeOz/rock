<?php
namespace rock\validation\rules;

class Alpha extends AbstractCtypeRule
{
    protected function filter($input)
    {
        return $this->filterWhiteSpaceOption($input);
    }

    protected function ctypeFunction($input)
    {
        return ctype_alpha($input);
    }
}

