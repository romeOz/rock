<?php

namespace rock\validation\rules;

use rock\file\UploadedFile;
use rock\validation\Exception;

class Uploaded extends AbstractRule
{
    public function validate($input)
    {
        if ($input instanceof UploadedFile) {
            if ($input->error === UPLOAD_ERR_OK) {
                return true;
            }
            if ($input->error === UPLOAD_ERR_NO_FILE) {
                return false;
            }

            switch ($input->error) {
 /*               case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    //$this->addRule(new FileSizeMax(UploadedFile::getSizeLimit()));
                    return false;*/
                case UPLOAD_ERR_PARTIAL:
                    new Exception(Exception::WARNING, 'File was only partially uploaded: '. $input->name);
                    return false;
                case UPLOAD_ERR_NO_TMP_DIR:
                    new Exception(Exception::WARNING, 'Missing the temporary folder to store the uploaded file: '. $input->name);
                    return false;
                case UPLOAD_ERR_CANT_WRITE:
                    new Exception(Exception::WARNING, 'Failed to write the uploaded file to disk: '. $input->name);
                    return false;
                case UPLOAD_ERR_EXTENSION:
                    new Exception(Exception::WARNING, 'File upload was stopped by some PHP extension: '. $input->name);
                    return false;
                default:
                    break;
            }
            return true;
        }

        if ($input instanceof \SplFileInfo) {
            $input = $input->getPathname();
        }

        return (is_string($input) && is_uploaded_file($input));
    }

}

