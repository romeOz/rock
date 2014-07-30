<?php
namespace rock\validation\rules;

class Vowels extends Vowel
{
    public function __construct()
    {
        parent::__construct();
        trigger_error("Use consonant instead.",
            E_USER_DEPRECATED);
    }
}
