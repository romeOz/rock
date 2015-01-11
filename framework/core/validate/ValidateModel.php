<?php

namespace rock\validate;


use rock\base\Model;
use rock\validate\rules\Unique;

/**
 * Class ValidateModel
 *
 * @method static Validate unique(Model $m, $targetAttribute = null, $targetClass = null, $filter = null)
 *
 * @package rock\validate
 */
class ValidateModel extends Validate
{
    protected function defaultRules()
    {
        return array_merge(parent::defaultRules(), $this->modelRules());
    }

    public function existsModelRule($name)
    {
        $rules = $this->modelRules();
        return !empty($rules) && isset($rules[$name]);
    }

    protected function modelRules()
    {
        return [
            'unique' => [
                'class' => Unique::className(),
                'locales' => [
                    self::EN => \rock\validate\locale\en\Unique::className(),
                    //self::RU => \rock\validate\locale\ru\Uploaded::className(),
                ]
            ],
        ];
    }
}