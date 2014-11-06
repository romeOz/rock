<?php
namespace rock\file;

use rock\exception\BaseException;

class FileException extends BaseException
{
    const FILE_EXISTS = 'File exists: {path}.';
}