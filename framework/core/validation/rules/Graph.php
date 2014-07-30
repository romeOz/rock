<?php
namespace rock\validation\rules;

class Graph extends AbstractCtypeRule
{
    protected function ctypeFunction($input)
    {
        return ctype_graph($input);
    }
}

