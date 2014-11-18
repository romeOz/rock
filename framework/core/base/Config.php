<?php
namespace rock\base;

use rock\helpers\Helper;

class Config
{
    protected static $configs = [];
    protected static $configsPublic = [];


    /**
     * Set array configs
     *
     * @param array $configs
     */
    public static function set(array $configs)
    {
        $all = [];
        $public = [];
        foreach ($configs as $key => $value) {
            if ($key{0} !== '_') {
                $public[$key] = $value;
            }
            $all[ltrim($key, '_')] = $value;
        }
        static::$configs = $all;
        static::$configsPublic = $public;
    }

    /**
     * Get data of config
     *
     * @param string $key key
     * @param bool   $onlyPublic only public config (default: false)
     * @return mixed
     */
    public static function get($key, $onlyPublic = false)
    {
        return $onlyPublic === true
            ? Helper::getValueIsset(static::$configsPublic[$key])
            : Helper::getValueIsset(
                static::$configs[$key]);
    }

    /**
     * Get all data of config
     *
     * @param bool $onlyPublic only public config (default: false)
     * @return mixed
     */
    public static function getAll($onlyPublic = false)
    {
        return $onlyPublic === true ? static::$configsPublic : static::$configs;
    }
}