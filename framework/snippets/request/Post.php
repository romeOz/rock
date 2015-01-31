<?php
namespace rock\snippets\request;
use rock\request\Request;
use rock\sanitize\Sanitize;
use rock\template\Snippet;

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
        if (empty($this->name)){
            return Request::postAll($this->filters);
        }

        if (!isset($_POST[$this->name])) {
            return Request::post($this->name, $this->default, $this->filters);
        }

        return null;
    }
}