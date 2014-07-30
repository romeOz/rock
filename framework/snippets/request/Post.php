<?php
namespace rock\snippets\request;
use rock\base\Snippet;

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
            return $this->Rock->request->postAll($this->filters);
        }

        if (!isset($_POST[$this->name])) {
            return $this->Rock->request->post($this->name, $this->default, $this->filters);
        }

        return null;
    }
}