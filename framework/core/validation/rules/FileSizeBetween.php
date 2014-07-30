<?php

namespace rock\validation\rules;


use rock\helpers\File;
use rock\validation\exceptions\ComponentException;

class FileSizeBetween extends AllOf
{
    public $minValue;
    public $maxValue;

    public function __construct($min=null, $max=null, $inclusive=false)
    {
        $this->minValue = File::sizeToBytes($min);
        $this->maxValue = File::sizeToBytes($max);
        if (!is_null($min) && !is_null($max) && $min > $max) {
            throw new ComponentException(sprintf('%s cannot be less than  %s for validation', $min, $max));
        }

        if (!is_null($min)) {
            $this->addRule(new FileSizeMin($min, $inclusive));
        }

        if (!is_null($max)) {
            $this->addRule(new FileSizeMax($max, $inclusive));
        }
    }

}