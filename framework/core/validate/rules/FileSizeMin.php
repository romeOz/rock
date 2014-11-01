<?php

namespace rock\validate\rules;

use rock\file\UploadedFile;

class FileSizeMin extends Rule
{
    public function __construct($minValue, $inclusive = false, $config = [])
    {
        $this->parentConstruct($config);
        $this->params['minValue'] = \rock\helpers\File::sizeToBytes($minValue);
        $this->params['inclusive'] = $inclusive;
    }

    /**
     * @inheritdoc
     */
    public function validate($input)
    {
        $minValue = $this->params['minValue'];
        if ($input instanceof UploadedFile) {
            $input = $input->size;
        } elseif ($input instanceof \SplFileInfo) {
            $input = $input->getSize();
        }
        if ($this->params['inclusive']) {
            return $input >= $minValue;
        } else {
            return $input > $minValue;
        }
    }
} 