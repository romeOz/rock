<?php

namespace rockunit\core\template\snippets;


use rock\base\Snippet;

class TestSnippet extends Snippet
{
    public $param;

    public function get()
    {
        return $this->param;
    }
} 