<?php
namespace rock\snippets;
use rock\base\Snippet;

class CSRF extends Snippet
{
    /**
     * Name of CSRF-token.
     *
     * @var string
     */
    public $name;

    public function get()
    {
        if (empty($this->name)) {
            return false;
        }
        $token = $this->Rock->csrf;
        return $token->create($this->name);
    }
}
