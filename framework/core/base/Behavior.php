<?php

namespace rock\base;


use rock\base\ObjectTrait;

class Behavior implements WhenInterface
{
    use ObjectTrait;

    /** @var  object */
    public $owner;

    public function before()
    {
    }

    public function after()
    {
    }
//
//
//    protected $isValid = true;
//
//
//    public function isValid()
//    {
//        return $this->isValid;
//    }



} 