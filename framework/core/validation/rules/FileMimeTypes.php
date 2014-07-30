<?php

namespace rock\validation\rules;


use rock\file\UploadedFile;
use rock\helpers\File;
use rock\validation\Exception;

class FileMimeTypes extends AbstractRule
{
    public $mimeTypes = [];

    public function __construct($mimeTypes)
    {
        $this->mimeTypes = $this->toArray($mimeTypes);
    }

    public function validate($input)
    {
        if ($input instanceof UploadedFile) {
            if ($input->error !== UPLOAD_ERR_OK) {
                $this->mimeTypes = $this->toString($this->mimeTypes);
                return false;
            }
            $input =  $input->tempName;
        }

        if (in_array(File::getMimeType($input), $this->toArray($this->mimeTypes), true)) {
            return true;
        }

        if (is_array($this->mimeTypes)) {
            $this->mimeTypes = $this->toString($this->mimeTypes);
        }
        return false;
    }

    protected function toArray($value)
    {
        if (!is_array($value)) {
            return preg_split('/[\s,]+/', strtolower($value), -1, PREG_SPLIT_NO_EMPTY);
        }
        return $value;
    }

    protected function toString($value)
    {
        if (is_array($value)) {
            return implode(', ', $value);
        }
        return $value;
    }
} 