<?php

namespace rock\validation\rules;


use rock\file\UploadedFile;
use rock\helpers\File;

class FileSizeMin extends AbstractRule
{
    public $inclusive;
    public $minValue;

    public function __construct($minValue, $inclusive=false)
    {
        $this->minValue = File::sizeToBytes($minValue);
        $this->inclusive = $inclusive;
    }

    public function validate($input)
    {
        $minValue = $this->minValue;
        if ($input instanceof UploadedFile) {
            $input = $input->size;
        } elseif ($input instanceof \SplFileInfo) {
            $input = $input->getSize();
        }
        if ($this->inclusive) {
            return $input >= $minValue;
        } else {
            return $input > $minValue;
        }
    }
} 