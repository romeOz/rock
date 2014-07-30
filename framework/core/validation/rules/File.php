<?php

namespace rock\validation\rules;

class File extends AbstractRule
{

    public function validate($input)
    {
        if ($input instanceof \SplFileInfo) {
            return $input->isFile();
        }

        return (is_string($input) && is_file($input));
    }

}

