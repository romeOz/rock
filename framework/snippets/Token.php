<?php
namespace rock\snippets;
use rock\base\Snippet;

class Token extends Snippet
{
    /**
     * name of token
     *
     * @var string
     */
    public $name;


    public function get()
    {
        if (empty($this->name)) {
            return false;
        }
        $token = $this->Rock->token;
        return $token->create($this->name);
    }
}
