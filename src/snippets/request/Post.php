<?php
namespace rock\snippets\request;

use rock\request\Request;
use rock\sanitize\Sanitize;
use rock\snippets\Snippet;

/**
 * Snippet "Post request"
 *
 * @see Request
 */
class Post extends Snippet
{
    /**
     * name request var
     * @var string
     */
    public $name;
    public $filters;
    public $default;

    public $autoEscape = false;

    public function get()
    {
        if (is_array($this->filters)) {
            $this->filters = Sanitize::rules($this->filters);
        }

        return Request::post($this->name, $this->default, $this->filters);
    }
}