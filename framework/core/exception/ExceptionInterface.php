<?php

namespace rock\exception;


interface ExceptionInterface
{
    const PHP_VERSION_INVALID = 'PHP invalid version';
    const UNKNOWN_CLASS = 'Unknown class: {class}';
    const UNKNOWN_METHOD = 'Unknown method: {method}';
    const SETTING_UNKNOWN_PROPERTY = 'Setting unknown property: {class}::{property}';
    const SETTING_READ_ONLY_PROPERTY = 'Setting read-only property: {class}::{property}';
    const GETTING_UNKNOWN_PROPERTY = 'Getting unknown property: {class}::{property}';
    const GETTING_WRITE_ONLY_PROPERTY = 'Getting write-only property: {class}::{property}';
    const UNKNOWN_VAR = 'Unknown var: {name}';
    const UNKNOWN_FILE = 'Unknown file: {path}';
    const NOT_CREATE_FILE = 'Does not create file: {path}';
    const NOT_CREATE_DIR = 'Does not create dir: {path}';
    const NOT_CALLABLE = 'Does not callable: {name}';
    const NOT_OBJECT = 'Does not object: {name}';
    const NOT_ARRAY = 'Does not array: {name}';
    const WRONG_TYPE = 'Wrong type: {name}';

    /**
     * @param int             $level
     * @param string|null     $msg
     * @param array|null      $dataReplace
     * @param \Exception|null $handler
     */
    public function __construct($level, $msg = null, array $dataReplace = [], \Exception $handler = null);
}