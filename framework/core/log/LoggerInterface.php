<?php

namespace rock\log;


//use Monolog\Logger;

interface LoggerInterface
{
    /**
     * Detailed debug information
     */
    const DEBUG = \Monolog\Logger::DEBUG;

    /**
     * Interesting events
     *
     * Examples: User logs in, SQL logs.
     */
    const INFO = \Monolog\Logger::INFO;

    /**
     * Uncommon events
     */
    const NOTICE = \Monolog\Logger::NOTICE;

    /**
     * Exceptional occurrences that are not errors
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    const WARNING = \Monolog\Logger::WARNING;

    /**
     * Runtime errors
     * @var int
     */
    const ERROR = \Monolog\Logger::ERROR;

    /**
     * Critical conditions
     *
     * Example: Application component unavailable, unexpected exception.
     */
    const CRITICAL = \Monolog\Logger::CRITICAL;

    /**
     * Action must be taken immediately
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    const ALERT = \Monolog\Logger::ALERT;

    /**
     * Urgent alert.
     */
    const EMERGENCY = \Monolog\Logger::EMERGENCY;
} 