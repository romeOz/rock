<?php
namespace rock\event;

/**
 * Interface "IEventApp"
 *
 * @package rock\event
 */
interface EventAppInterface
{
    /**
     * Begin Application
     */
    public static function onBeginApp();

    /**
     * End Application
     */
    public static function onEndApp();

    /**
     * Begin Route
     */
    public static function onBeginRoute();

    /**
     * End Route
     */
    public static function onEndRoute();

    /**
     * Exists action
     */
    public static function onExistsAction();

    /**
     * Wrong action
     */
    public static function onWrongAction();
}