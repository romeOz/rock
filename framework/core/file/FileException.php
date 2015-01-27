<?php
namespace rock\file;

use rock\base\BaseException;

class FileException extends BaseException
{
    const FILE_EXISTS = 'File exists: {{path}}.';
}