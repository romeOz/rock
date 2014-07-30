<?php
namespace rock\file;

use rock\exception\BaseException;

class Exception extends BaseException
{
    const FILE_EXISTS = 'File exists: {path}';

    public function __construct(
        $level = self::ERROR,
        $msg = null,
        array $dataReplace = [],
        \Exception $handler = null
    ) {
        return parent::__construct($level, $msg, $dataReplace, $handler);
    }
}