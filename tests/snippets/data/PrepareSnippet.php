<?php

namespace rockunit\snippets\data;



use rock\snippets\Snippet;

class PrepareSnippet extends Snippet
{
    public $placeholders;

    public function get()
    {
        return $this->placeholders;
    }
} 