<?php

namespace rockunit\core\validate\mocks;


use rock\validate\rules\Uploaded;

class UploadedMock extends Uploaded
{
    public function validate($input)
    {
        if ($input instanceof \SplFileInfo) {
            return $GLOBALS['is_uploaded_file'];
        }
        return is_string($input) && $GLOBALS['is_uploaded_file'];
    }
} 