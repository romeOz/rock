<?php
namespace rock\validation\exceptions\ru;

use rock\validation\exceptions\AbstractNestedException;

class BetweenException extends AbstractNestedException
{
    const BOTH = 0;
    const LOWER = 1;
    const GREATER = 2;

    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::BOTH => '{{name}} должно быть между {{minValue}} и {{maxValue}}',
            self::LOWER => '{{name}}  должно быть больше чем {{minValue}}',
            self::GREATER => '{{name}} должно быть меньше чем {{maxValue}}',
        ),
        self::MODE_NEGATIVE => array(
            self::BOTH => '{{name}} не должно быть между {{minValue}} и {{maxValue}}',
            self::LOWER => '{{name}}  не должно быть больше {{minValue}}',
            self::GREATER => '{{name}} не должно быть меньше {{maxValue}}',
        )
    );

    public function configure($name, array $params=array())
    {
        $params['minValue'] = static::stringify($params['minValue']);
        $params['maxValue'] = static::stringify($params['maxValue']);

        return parent::configure($name, $params);
    }

    public function chooseTemplate()
    {
        if (!$this->getParam('minValue')) {
            return static::GREATER;
        } elseif (!$this->getParam('maxValue')) {
            return static::LOWER;
        } else {
            return static::BOTH;
        }
    }
}

