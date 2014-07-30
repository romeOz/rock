<?php
namespace rock\validation\exceptions;

class FileSizeBetweenException extends AbstractNestedException
{
    const BOTH = 0;
    const LOWER = 1;
    const GREATER = 2;

    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::BOTH => 'Size of {{name}} must be between {{minValue}} and {{maxValue}} bytes',
            self::LOWER => 'Size of {{name}}  must be greater than {{minValue}} bytes',
            self::GREATER => 'Size of {{name}} must be lower than {{maxValue}} bytes',
        ),
        self::MODE_NEGATIVE => array(
            self::BOTH => 'Size of {{name}} must not be between {{minValue}} and {{maxValue}} bytes',
            self::LOWER => 'Size of {{name}}  must not be greater than {{minValue}} bytes',
            self::GREATER => 'Size of {{name}} must not be lower than {{maxValue}} bytes',
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

