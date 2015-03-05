<?php

namespace rock\log;


use Monolog\Logger;

interface LogInterface
{
    /**
     * Detailed debug information
     */
    const DEBUG = Logger::DEBUG;

    /**
     * Interesting events
     *
     * Examples: User logs in, SQL logs.
     */
    const INFO = Logger::INFO;

    /**
     * Uncommon events
     */
    const NOTICE = Logger::NOTICE;

    /**
     * Exceptional occurrences that are not errors
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    const WARNING = Logger::WARNING;

    /**
     * Runtime errors
     * @var int
     */
    const ERROR = Logger::ERROR;

    /**
     * Critical conditions
     *
     * Example: Application component unavailable, unexpected exception.
     */
    const CRITICAL = Logger::CRITICAL;

    /**
     * Action must be taken immediately
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    const ALERT = Logger::ALERT;

    /**
     * Urgent alert.
     */
    const EMERGENCY = Logger::EMERGENCY;
} 