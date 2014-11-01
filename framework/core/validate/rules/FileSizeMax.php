<?php

namespace rock\validate\rules;

use rock\file\UploadedFile;

class FileSizeMax extends Rule
{
    public function __construct($maxValue = null, $inclusive = false, $config = [])
    {
        $this->parentConstruct($config);
        $this->params['maxValue'] = UploadedFile::getSizeLimit();
        $this->params['inclusive'] = $inclusive;
    }

    /**
     * @inheritdoc
     */
    public function validate($input)
    {
        $maxValue = $this->params['maxValue'];
        if ($input instanceof UploadedFile) {
            if ($input->error === UPLOAD_ERR_INI_SIZE || $input->error === UPLOAD_ERR_FORM_SIZE) {
                return false;
            }
            $input = $input->size;
        } elseif ($input instanceof \SplFileInfo) {
            $input = $input->getSize();
        }
        $this->params['maxValue'] = UploadedFile::getSizeLimit($this->params['maxValue']);
        if ($this->params['inclusive']) {
            return $input <= $maxValue;
        } else {
            return $input < $maxValue;
        }
    }
} 