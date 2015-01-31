<?php
namespace rock\snippets;
use rock\di\Container;
use rock\template\Snippet;

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
        /** @var \rock\csrf\CSRF $csrf */
        $csrf = Container::load('csrf');
        return $csrf->get();
    }
}
