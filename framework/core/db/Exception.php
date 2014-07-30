<?php
namespace rock\db;

use rock\exception\BaseException;

class Exception extends BaseException
{
    const NOT_SUPPORT_SCHEMA = 'Connection does not support reading schema information for {driver} DBMS.';
    const NOT_SUPPORT_RESETTING = '{driver} does not support resetting sequence.';
    const NOT_SUPPORT_INTEGRITY_CHECK = '{driver} does not support enabling/disabling integrity check.';
    const JOIN_IS_NOT_ARRAY = 'A join clause must be specified as an array of join type, join table, and optionally join condition.';
    const UNKNOWN_OPERATOR = 'Found unknown operator in query: {operator}';

    /**
     * Constructor
     *
     * @param int|string      $level       - type of exception
     * @param string|null     $msg         - message
     * @param array|null      $dataReplace - array replace
     * @param \Exception|null $handler     - handler
     */
    public function __construct($level = self::ERROR, $msg = null, array $dataReplace = [], \Exception $handler = null){
        parent::__construct($level, $msg, $dataReplace, $handler);
    }
}
