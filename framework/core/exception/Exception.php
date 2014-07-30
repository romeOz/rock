<?php
namespace rock\exception;


class Exception extends BaseException
{

    /**
     * Constructor
     *
     * @param int|string      $level       - type of exception
     * @param string|int|null $msg         - message or code
     *
     * @param array           $dataReplace - array replace
     * @param \Exception      $handler     - handler
     */
    public function __construct($level = self::ERROR, $msg = null, array $dataReplace = [], \Exception $handler = null) {
        parent::__construct($level, $msg, $dataReplace, $handler);
    }
}