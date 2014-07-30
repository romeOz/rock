<?php

namespace rock\validation\rules;


use rock\file\UploadedFile;

class FileSizeMax extends AbstractRule
{
    public $maxValue;
    public $inclusive;

    public function __construct($maxValue = null, $inclusive=false)
    {
        $this->maxValue = UploadedFile::getSizeLimit();
        $this->inclusive = $inclusive;
    }

    public function validate($input)
    {
        $maxValue = $this->maxValue;

        if ($input instanceof UploadedFile) {
            if ($input->error === UPLOAD_ERR_INI_SIZE || $input->error === UPLOAD_ERR_FORM_SIZE) {
                return false;
            }
            $input = $input->size;
        } elseif ($input instanceof \SplFileInfo) {
            $input = $input->getSize();
        }
        $this->maxValue = UploadedFile::getSizeLimit($this->maxValue);
        if ($this->inclusive) {
            return $input <= $maxValue;
        } else {
            return $input < $maxValue;
        }
    }
} 