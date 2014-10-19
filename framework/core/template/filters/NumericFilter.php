<?php

namespace rock\template\filters;


use rock\base\ClassName;
use rock\helpers\Numeric;
use rock\template\Exception;
use rock\template\Template;

class NumericFilter
{
    use ClassName;

    /**
     * Check numeric is parity.
     *
     * @param string   $value
     * @param array    $params:
     *
     * - then
     * - else
     *
     * @param Template $template
     * @throws \rock\template\Exception
     * @return string
     */
    public static function isParity($value, array $params, Template $template)
    {
        if (empty($params) || count($params) < 1 || !isset($params['then'])) {
            throw new Exception(Exception::ERROR, Exception::UNKNOWN_PARAM_FILTER, ['name' => __METHOD__]);
        }
        $params['else'] = isset($params['else']) ? $params['else'] : null;
        $template = clone $template;
        $placeholders = [];
        $placeholders['output'] = $value;

        return Numeric::isParity($value)
            ? $template->replace($params['then'], $placeholders)
            : $template->replace($params['else'], $placeholders);
    }

    /**
     * Number convert to positive.
     *
     * @param int $value
     * @return int
     */
    public static function positive($value)
    {
        return Numeric::toPositive($value);
    }

    /**
     * The value is calculated by the formula
     *
     * @param float|int|number $value
     * @param array $params:
     *
     * - operator: arithmetic and bitwise operators: `*`, `**`, `+`, `-`, `/`, `%`, `|`, `&`, `^`, `<<`, `>>`
     * - operand
     *
     * @return float|int|number
     * @throws \rock\template\Exception
     *
     * ```php
     * (new \rock\Template())->replace('[[+num:formula&operator=`*`&operand=`4`]]', ['num'=> 3]); // 12
     *
     * // sugar
     * (new \rock\Template())->replace('[[+num * `4`]]', ['num'=> 3]); // 12
     * ```
     */
    public static function formula($value, array $params = [])
    {
        if (empty($params['operator']) || !isset($params['operand'])) {
            return $value;
        }
        switch (trim($params['operator'])) {
            case '*':
                return $value * $params['operand'];
            case '/':
                return $value / $params['operand'];
            case '+':
                return $value + $params['operand'];
            case '-':
                return $value - $params['operand'];
            case '**':
                return pow($value, $params['operand']);
            case 'mod':
            case '%':
                return $value % $params['operand'];
            case '|':
                return $value | $params['operand'];
            case '&':
                return $value & $params['operand'];
            case '^':
            case 'xor':
                return $value ^ $params['operand'];
            case '<<':
                return $value << $params['operand'];
            case '>>':
                return $value >> $params['operand'];
        }
        throw new Exception(Exception::ERROR, "Unknown operator: {$params['operator']}");
    }
}