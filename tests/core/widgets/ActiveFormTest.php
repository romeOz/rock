<?php

namespace rockunit\core\widgets;


use rock\widgets\ActiveForm;
use rockunit\core\widgets\mocks\ModelMock;

class ActiveFormTest extends \PHPUnit_Framework_TestCase 
{
    public function assertEqualsWithoutLE($expected, $actual)
    {
        $expected = str_replace("\r\n", "\n", $expected);
        $actual = str_replace("\r\n", "\n", $actual);

        $this->assertEquals($expected, $actual);
    }

    public function testBooleanAttributes()
    {
        $o = ['template' => '{input}'];

        $model = new ModelMock(['name']);
        ob_start();
        $form = new ActiveForm(['model' => $model, 'action' => '/something', 'enableClientValidation' =>false]);
        ob_end_clean();

        $this->assertEqualsWithoutLE(<<<EOF
<div class="form-group field-modelmock-name">
<input type="email" id="modelmock-name" class="form-control" name="ModelMock[name]" required data-ng-model="ModelMock.values.name">
</div>
EOF
            , (string) $form->field($model, 'name', $o)->input('email', ['required' => true]));

        $this->assertEqualsWithoutLE(<<<EOF
<div class="form-group field-modelmock-name">
<input type="email" id="modelmock-name" class="form-control" name="ModelMock[name]" data-ng-model="ModelMock.values.name">
</div>
EOF
            , (string) $form->field($model, 'name', $o)->input('email', ['required' => false]));


        $this->assertEqualsWithoutLE(<<<EOF
<div class="form-group field-modelmock-name">
<input type="email" id="modelmock-name" class="form-control" name="ModelMock[name]" required="test" data-ng-model="ModelMock.values.name">
</div>
EOF
            , (string) $form->field($model, 'name', $o)->input('email', ['required' => 'test']));

    }

    public function testIssue5356()
    {
        $o = ['template' => '{input}'];

        $model = new ModelMock(['categories']);
        $model->categories = 1;
        ob_start();
        $form = new ActiveForm(['model' => $model, 'action' => '/something', 'enableClientValidation' =>false]);
        ob_end_clean();

        // https://github.com/yiisoft/yii2/issues/5356
        $this->assertEqualsWithoutLE(<<<EOF
<div class="form-group field-modelmock-categories">
<input type="hidden" name="ModelMock[categories]" value=""><select id="modelmock-categories" class="form-control" name="ModelMock[categories][]" multiple size="4">
<option value="0">apple</option>
<option value="1" selected>banana</option>
<option value="2">avocado</option>
</select>
</div>
EOF
            , (string) $form->field($model, 'categories', $o)->listBox(['apple', 'banana', 'avocado'], ['multiple' => true]));
    }
}
