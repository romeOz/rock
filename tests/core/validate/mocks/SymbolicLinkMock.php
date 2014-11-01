<?php

namespace rockunit\core\validate\mocks;


use rock\validate\rules\SymbolicLink;

class SymbolicLinkMock extends SymbolicLink
{
    public function validate($input)
    {
        if ($input instanceof \SplFileInfo) {
            return $input->isLink();
        }
        return is_string($input) && $GLOBALS['is_link'];
    }
} 