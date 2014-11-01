<?php
namespace rockunit\core\base;

use rock\base\Model;
use rock\Rock;

class FooModal extends Model
{
    public $rules;
    public $username;
    public $email;
    public $age;
    public $password;

    public function rules()
    {
        return $this->rules;
    }

    public function safeAttributes()
    {
        return ['username', 'email', 'age'];
    }

    public function attributeLabels()
    {
        return ['email' => Rock::t('email'), 'username' => Rock::t('username')];
    }


    public function customFilter($input = '', $punctuation = '')
    {
        return $input . $punctuation;
    }

    public function customValidate($input = '', $attributeName)
    {
        if ($input === '') {
            return true;
        }
        $placeholders = ['name' => 'value'];
        if (is_string($input)) {
            if (($label = $this->attributeLabels()) && isset($label[$attributeName])) {
                $placeholders['name'] = $label[$attributeName];
            }
            $this->addError($attributeName, Rock::t('call', $placeholders, 'validate'));
            return false;
        }
        return true;
    }
}
/**
 * @group base
 */
class ModelRulesTest extends \PHPUnit_Framework_TestCase
{
    public function testFilter()
    {
        $model = new FooModal();
        $model->rules = [
            [
                FooModal::RULE_SANITIZE, ['email', 'username', 'age'], 'trim'
            ],
            [
                FooModal::RULE_VALIDATE, ['email', 'username'], 'required'
            ],
            [
                FooModal::RULE_SANITIZE, 'email', 'mb_strtolower' => [Rock::$app->charset], 'removeTags'
            ],
            [
                FooModal::RULE_SANITIZE, 'username', 'customFilter' => ['.']
            ],
        ];
        $model->setAttributes(['username' => 'Tom   ', 'email' => ' <b>ToM@site.com</b>   ', 'password' => 'qwerty']);
        $this->assertTrue($model->validate());
        $expected = [
            'username' => 'Tom.',
            'email' => 'tom@site.com',
            'age' => null,
            'password' => null,
        ];
        $this->assertSame($expected, $model->getAttributes([], ['rules']));
    }

    public function testValidate()
    {
        $model = new FooModal();
        $model->rules = [
            [
                FooModal::RULE_SANITIZE, ['email', 'username'], 'trim'
            ],
            [
                FooModal::RULE_VALIDATE, ['email', 'username'], 'required', 'customValidate'
            ],
            [
                FooModal::RULE_VALIDATE, 'email', 'length' => [20, 80, true], 'email'
            ],
            [
                FooModal::RULE_VALIDATE, 'username', 'length' => [6, 20], 'regex' => ['/^[a-z\d\-\_\.]+$/i'],
                'placeholders' => ['name' => 'foo']
            ],
            [
                FooModal::RULE_VALIDATE, 'age', 'is_int', 'messages' => ['is_int' => 'error']
            ],
            [
                FooModal::RULE_SANITIZE, 'email', 'mb_strtolower' => [Rock::$app->charset]
            ],
            [
                FooModal::RULE_SANITIZE, 'username', 'customFilter' => ['.']
            ],
        ];
        $model->setAttributes(['username' => 'T(o)m   ', 'email' => ' ToM@site.com   ', 'password' => 'qwerty']);
        $this->assertFalse($model->validate());
        $expected = [
            'email' =>
                [
                    0 => 'e-mail must be valid',
                    1 => 'e-mail must have a length between 20 and 80',
                ],
            'username' =>
                [
                    'username must be valid',
                    'foo must have a length between 6 and 20',
                    'foo contains invalid characters',
                ],
            'age' =>
                [
                    0 => 'error',
                ],
        ];
        $this->assertSame($expected, $model->getErrors());
        $expected = [
            'username' => 'T(o)m',
            'email' => 'ToM@site.com',
            'age' => null,
            'password' => null,
        ];
        $this->assertSame($expected, $model->getAttributes([], ['rules']));
    }

    public function testValidateSkipEmpty()
    {
        $model = new FooModal();
        $model->rules = [
            [
                FooModal::RULE_SANITIZE, ['email', 'username'], 'trim'
            ],
            [
                FooModal::RULE_VALIDATE, ['email', 'username'], 'required', 'customValidate'
            ],
            [
                FooModal::RULE_VALIDATE, 'username', 'length' => [6, 20], 'regex' => ['/^[a-z\d\-\_\.]+$/i'],
                'placeholders' => ['name' => 'foo']
            ],
            [
                FooModal::RULE_VALIDATE, 'age', 'is_int', 'messages' => ['is_int' => 'error']
            ],
            [
                FooModal::RULE_SANITIZE, 'email', 'mb_strtolower' => [Rock::$app->charset]
            ],
            [
                FooModal::RULE_SANITIZE, 'username', 'customFilter' => ['.']
            ],
        ];
        $model->setAttributes(['username' => '', 'email' => '', 'password' => '', 'age' => '']);
        $this->assertFalse($model->validate());
        $expected = [
            'email' =>
                [
                    0 => 'e-mail must not be empty',
                ],
            'username' =>
                [
                    0 => 'username must not be empty',
                ],
        ];
        $this->assertSame($expected, $model->getErrors());
        $expected = [
            'username' => '',
            'email' => '',
            'age' => '',
            'password' => null,
        ];
        $this->assertSame($expected, $model->getAttributes([], ['rules']));
    }

    public function testCustomMessage()
    {
        $model = new FooModal();
        $model->rules = [
            [
                FooModal::RULE_VALIDATE, ['age'], 'customValidate', 'length' => [6, 20],
                'messages' => ['length' => 'error length']
            ],
        ];
        $model->setAttributes(['age' => '25']);
        $this->assertFalse($model->validate());
        $expected = [
            'age' =>
                [
                    'value must be valid',
                    'error length',
                ],
        ];
        $this->assertSame($expected, $model->errors);
    }

    /**
     * @expectedException \rock\base\ModelException
     */
    public function testFilterThrowExceptionArgumentsMustBeArray()
    {
        $model = new FooModal();
        $model->rules = [
            [
                FooModal::RULE_SANITIZE, 'email', 'mb_strtolower' => 'exception'
            ],
        ];
        $model->setAttributes(['username' => 'Tom   ', 'email' => ' ToM@site.com   ', 'password' => 'qwerty']);
        $model->validate();
    }

    /**
     * @expectedException \rock\base\ModelException
     */
    public function testValidateThrowExceptionArgumentsMustBeArray()
    {
        $model = new FooModal();
        $model->rules = [
            [
                FooModal::RULE_VALIDATE, 'email', 'is_int' => 'exception'
            ],
        ];
        $model->setAttributes(['username' => 'Tom   ', 'email' => ' ToM@site.com   ', 'password' => 'qwerty']);
        $model->validate();
    }

    /**
     * @expectedException \rock\base\ModelException
     */
    public function testThrowExceptionUnknownTypeRule()
    {
        $model = new FooModal();
        $model->rules = [
            [
                'unknown', 'email', 'is_int' => 'exception'
            ],
        ];
        $model->setAttributes(['username' => 'Tom   ', 'email' => ' ToM@site.com   ', 'password' => 'qwerty']);
        $model->validate();
    }

    public function testScenario()
    {
        $model = new FooModal();
        $model->rules = [
            [
                FooModal::RULE_VALIDATE, ['email', 'username'], 'required', 'customValidate', 'scenarios' => ['baz']
            ],
            [
                FooModal::RULE_VALIDATE, 'username', 'length' => [6, 20], 'regex' => ['/^[a-z\d\-\_\.]+$/i'],
                'placeholders' => ['name' => 'foo'] , 'scenarios' => 'bar'
            ],

        ];
        $model->setAttributes(['username' => 'Tom']);
        $model->scenario = 'bar';
        $this->assertFalse($model->validate());
        $expected = [
            'username' =>
                [
                   'foo must have a length between 6 and 20',
                ],
        ];
        $this->assertSame($expected, $model->getErrors());


        $model = new FooModal();
        $model->rules = [
            [
                FooModal::RULE_VALIDATE, ['email', 'username'], 'required', 'customValidate', 'scenarios' => 'baz'
            ],
            [
                FooModal::RULE_VALIDATE, 'username', 'length' => [6, 20], 'regex' => ['/^[a-z\d\-\_\.]+$/i'],
                'placeholders' => ['name' => 'foo'] , 'scenarios' => 'bar'
            ],

        ];
        $model->setAttributes(['username' => 'Tom']);
        $model->scenario = 'bar';
        $this->assertFalse($model->validate());
        $expected = [
            'username' =>
                [
                    'foo must have a length between 6 and 20',
                ],
        ];
        $this->assertSame($expected, $model->getErrors());
    }

    public function testOneRule()
    {
        $model = new FooModal();
        $model->rules = [
            [
                FooModal::RULE_VALIDATE, ['email', 'username','age'], 'required', 'one'
            ],
            [
                FooModal::RULE_VALIDATE, 'username', 'length' => [6, 20], 'regex' => ['/^[a-z\d\-\_\.]+$/i']
            ],

        ];
        $model->setAttributes(['username' => 'Tom']);
        $this->assertFalse($model->validate());
        $expected = [
            'email' =>
                [
                    0 => 'e-mail must not be empty',
                ],
        ];
        $this->assertSame($expected, $model->getErrors());
    }

    public function testOneRuleByAttribute()
    {
        $model = new FooModal();
        $model->rules = [
            [
                FooModal::RULE_VALIDATE, ['email', 'username','age'], 'required', 'one' => 'email'
            ],
            [
                FooModal::RULE_VALIDATE, 'username', 'length' => [6, 20], 'regex' => ['/^[a-z\d\-\_\.]+$/i']
            ],

        ];
        $model->setAttributes(['username' => 'Tom']);
        $this->assertFalse($model->validate());
        $expected = [
            'email' =>
                [
                    0 => 'e-mail must not be empty',
                ],
        ];
        $this->assertSame($expected, $model->getErrors());

    }

    public function testWhen()
    {
        $model = new FooModal();
        $model->rules = [
            [
                FooModal::RULE_VALIDATE, ['email', 'username'], 'required', 'when' => ['length' => [6, 20], function($input, $attributeName) use($model){
                if (!preg_match('/^[a-z\\d\-\_\.]+$/i',$input)) {
                    $model->addError($attributeName, 'err');
                    return false;
                }
                return true;
            }]
            ],
        ];
        $model->setAttributes(['username' => 'Tom', 'email' => 'tom@site.com']);
        $this->assertFalse($model->validate());
        $expected = [
            'email' => [
                'err'
            ],
            'username' =>
                [
                    'username must have a length between 6 and 20',
                ],
        ];
        $this->assertSame($expected, $model->getErrors());
    }
}