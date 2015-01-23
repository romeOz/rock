<?php
namespace rock\db;

use rock\base\BaseException;

class DbException extends BaseException
{
    const NOT_SUPPORT_SCHEMA = 'Connection does not support reading schema information for {driver} DBMS.';
    const NOT_SUPPORT_RESETTING = '{driver} does not support resetting sequence.';
    const NOT_SUPPORT_INTEGRITY_CHECK = '{driver} does not support enabling/disabling integrity check.';
    const JOIN_IS_NOT_ARRAY = 'A join clause must be specified as an array of join type, join table, and optionally join condition.';
    const UNKNOWN_OPERATOR = 'Found unknown operator in query: {operator}';
}
