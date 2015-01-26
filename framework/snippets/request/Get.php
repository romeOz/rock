<?php
namespace rock\snippets\request;
use rock\core\Snippet;
use rock\request\Request;
use rock\sanitize\Sanitize;

/**
 * Snippet "Get request"
 *
 * @see Request
 */
class Get extends Snippet
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
            return Request::getAll($this->filters);
        }

        if (!isset($_POST[$this->name])) {
            return Request::get($this->name, $this->default, $this->filters);
        }

        return null;
    }
}