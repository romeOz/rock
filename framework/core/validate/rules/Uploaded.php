<?php

namespace rock\validate\rules;

use rock\file\UploadedFile;
use rock\Rock;

/**
 * Uploaded rule.
 *
 * @codeCoverageIgnore
 * @package rock\validate\rules
 */
class Uploaded extends Rule
{
    /**
     * @inheritdoc
     */
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
                    Rock::warning('File was only partially uploaded: '. $input->name);
                    return false;
                case UPLOAD_ERR_NO_TMP_DIR:
                    Rock::warning('Missing the temporary folder to store the uploaded file: '. $input->name);
                    return false;
                case UPLOAD_ERR_CANT_WRITE:
                    Rock::warning('Failed to write the uploaded file to disk: '. $input->name);
                    return false;
                case UPLOAD_ERR_EXTENSION:
                    Rock::warning('File upload was stopped by some PHP extension: '. $input->name);
                    return false;
                default:
                    break;
            }
            return true;
        }

        if ($input instanceof \SplFileInfo) {
            $input = $input->getPathname();
        }

        return is_string($input) && is_uploaded_file($input);
    }
} 