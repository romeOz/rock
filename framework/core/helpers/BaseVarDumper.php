<?php

namespace rock\helpers;


class BaseVarDumper
{
    /**
     * Parse array
     *
     * @param array $data           - array
     * @param bool  $return         - flag
     *                              => true  - to string
     *                              => false - to array
     * @return string|null
     */
    public static function pr(array $data, $return = false)
    {
        if ($return === true) {
            ob_start();
            echo '<pre>';
            var_dump($data);
            echo '</pre>';
            $content = ob_get_contents();
            ob_end_clean();

            return $content;
        }
        echo '<pre>';
        print_r($data);
        echo '</pre>';

        return null;
    }


    /**
     * Get array of debug
     *
     * @param array $data - array data of debug
     * @return void
     */
    public static function getDebug(array $data)
    {
        foreach ($data as $key => $value) {
            static::pr([$key => $value]);
        }
    }
} 