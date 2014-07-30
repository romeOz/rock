<?php

namespace rock\validation\exceptions;



class ConfirmException extends ValidationException
{
    const STANDARD = 0;
    const NAMED = 1;

    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => 'values must be equals',
            //self::NAMED => '{{name}} must be equals',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => 'values must not be equals',
            //self::NAMED => '{{name}} must not be equals',
        )
    );


//    public function chooseTemplate()
//    {
//        return $this->getName() == "" ? static::STANDARD : static::NAMED;
//    }

} 