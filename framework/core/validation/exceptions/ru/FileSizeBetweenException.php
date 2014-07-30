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
            self::BOTH => 'Размер {{name}} должен быть между {{minValue}} и {{maxValue}} байт',
            self::LOWER => 'Размер {{name}} должен быть больше чем {{minValue}} байт',
            self::GREATER => 'Размер {{name}} должен быть меньше чем {{maxValue}} байт',
        ),
        self::MODE_NEGATIVE => array(
            self::BOTH => 'Размер {{name}} не должен быть между {{minValue}} и {{maxValue}} байт',
            self::LOWER => 'Размер {{name}} не должен быть больше {{minValue}} байт',
            self::GREATER => 'Размер {{name}} не должен быть меньше {{maxValue}} байт',
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

