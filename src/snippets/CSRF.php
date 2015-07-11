<?php
namespace rock\snippets;
use rock\di\Container;

class CSRF extends Snippet
{
    public function get()
    {
        /** @var \rock\csrf\CSRF $csrf */
        $csrf = Container::load('csrf');
        return $csrf->get();
    }
}
