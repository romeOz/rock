<?php
namespace rock\snippets\request;
use rock\base\Snippet;

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
        if (empty($this->name)){
            return $this->Rock->request->getAll($this->filters);
        }

        if (!isset($_POST[$this->name])) {
            return $this->Rock->request->get($this->name, $this->default, $this->filters);
        }

        return null;
    }
}