<?php
namespace rock\snippets\request;
use rock\base\Snippet;
use rock\request\Request;

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
        if (empty($this->name)){
            return Request::postAll($this->filters);
        }

        if (!isset($_POST[$this->name])) {
            return Request::post($this->name, $this->default, $this->filters);
        }

        return null;
    }
}