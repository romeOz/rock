<?php

namespace rock\validation\rules;


use rock\file\UploadedFile;
use rock\helpers\File;
use rock\validation\Exception;

class FileExtensions extends AbstractRule
{
    public $extensions = [];
    public $checkExtensionByMimeType = true;

    public function __construct($extensions, $checkExtensionByMimeType = true)
    {
        $this->extensions = $this->toArray($extensions);
        $this->checkExtensionByMimeType = $checkExtensionByMimeType;
    }

    public function validate($input)
    {
        if ($input instanceof UploadedFile) {
            if ($input->error !== UPLOAD_ERR_OK) {
                $this->extensions = $this->toString($this->extensions);
                return false;
            }
            $extension = mb_strtolower($input->extension, 'utf-8');
            $input =  $input->tempName;
        } elseif(is_string($input)) {
            if (!$extension = strtolower(pathinfo($input, PATHINFO_EXTENSION))) {
                $this->extensions = $this->toString($this->extensions);
                return false;
            }
            //$extension = $extension['extension'];
        } else {
            throw new Exception(Exception::CRITICAL, Exception::UNKNOWN_VAR, ['name'=> 'input']);
        }

        if ($this->checkExtensionByMimeType) {

            $mimeType = File::getMimeType($input);
            if ($mimeType === null) {
                $this->extensions = $this->toString($this->extensions);
                return false;
            }

            $extensionsByMimeType = File::getExtensionsByMimeType($mimeType);

            if (!in_array($extension, $extensionsByMimeType, true)) {
                $this->extensions = $this->toString($this->extensions);
                return false;
            }
        }
        if (!in_array($extension, $this->toArray($this->extensions), true)) {
            $this->extensions = $this->toString($this->extensions);
            return false;
        }

        return true;
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