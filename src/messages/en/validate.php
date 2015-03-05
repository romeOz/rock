<?php
return [
    'required' => '{{name}} must not be empty',
    'notRequired' => '{{name}} must be empty',

    'min' => '{{name}} must be greater than {{minValue}}',
    'minInclusive' => '{{name}} must be greater than or equals {{minValue}}',
    'notMin' => '{{name}} must not be greater than {{minValue}}',
    'notMinInclusive' => '{{name}} must not be greater than or equals {{minValue}}',

    'max' => '{{name}} must be lower than {{maxValue}}',
    'maxInclusive' => '{{name}} must be lower than or equals {{maxValue}}',
    'notMax' => '{{name}} must not be lower than {{maxValue}}',
    'notMaxInclusive' => '{{name}} must not be lower than or equals {{maxValue}}',

    'email' => '{{name}} must be valid',
    'notEmail' => '{{name}} must not be valid',

    'regex' => '{{name}} contains invalid characters',
    'notRegex' => '{{name}} does not contain invalid characters',

    'captcha' =>  'captcha must be valid',
    'notCaptcha' => 'captcha must not be valid',

    'confirm' => 'values must be equals',
    'notConfirm' => 'values must not be equals',

    'call' => '{{name}} must be valid',

    'unique' => '{{value}} has already been taken',
    'notUnique' => '{{value}} not already been taken.'
];
 