<?php

namespace rockunit\core\widgets;


use rock\widgets\ActiveField;
use rock\widgets\ActiveForm;
use rockunit\core\widgets\mocks\ModelMock;

class ActiveFieldTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ActiveFieldExtend */
    private $activeField;
    /** @var  ModelMock */
    private $helperModel;
    private $helperForm;
    private $attributeName = 'attributeName';

    public function setUp()
    {
        $this->helperModel = new ModelMock(['attributeName']);
        ob_start();
        $this->helperForm = new ActiveForm(['model' => $this->helperModel, 'action' => '/something']);
        ob_end_clean();

        $this->activeField = new ActiveFieldExtend(true);
        $this->activeField->form = $this->helperForm;
        $this->activeField->model = $this->helperModel;
        $this->activeField->attribute = $this->attributeName;
    }

    public function testRenderNoContent()
    {
        $expectedValue = <<<EOD
<div class="form-group field-modelmock-attributename">
<label class="form-label" for="modelmock-attributename">Attribute Name</label>
<input type="text" id="modelmock-attributename" class="form-control" name="ModelMock[{$this->attributeName}]" data-ng-model="form.values.attributeName">


</div>
EOD;

        $actualValue = $this->activeField->render();
        $this->assertEquals($expectedValue, $actualValue);
    }

    public function testRenderWithCallableContent()
    {
        // field will be the html of the model's attribute wrapped with the return string below.
        $content = function($field) {
            return "<div class=\"custom-container\"> $field </div>";
        };

        $expectedValue = <<<EOD
<div class="form-group field-modelmock-attributename">
<div class="custom-container"> <div class="form-group field-modelmock-attributename">
<label class="form-label" for="modelmock-attributename">Attribute Name</label>
<input type="text" id="modelmock-attributename" class="form-control" name="ModelMock[{$this->attributeName}]" data-ng-model="form.values.attributeName">


</div> </div>
</div>
EOD;

        $actualValue = $this->activeField->render($content);
        $this->assertEquals($expectedValue, $actualValue);
    }

    public function testBeginHasErrors()
    {
        $this->helperModel->addError($this->attributeName, "Error Message");

        $expectedValue = '<div class="form-group field-modelmock-attributename has-error">';
        $actualValue = $this->activeField->begin();

        $this->assertEquals($expectedValue, $actualValue);
    }

    public function testBeginAttributeIsRequired()
    {
        $model = $this->helperModel;
        $model->rules = [
            [
                $model::RULE_VALIDATE, $this->attributeName, 'required'
            ],
        ];

        $expectedValue = '<div class="form-group field-modelmock-attributename required">';
        $actualValue = $this->activeField->begin();

        $this->assertEquals($expectedValue, $actualValue);
    }

    public function testBeginHasErrorAndRequired()
    {
        $model = $this->helperModel;
        $model->addError($this->attributeName, "Error Message");
        $model->rules = [
            [
                $model::RULE_VALIDATE, $this->attributeName, 'required'
            ],
        ];

        $expectedValue = '<div class="form-group field-modelmock-attributename required has-error">';
        $actualValue = $this->activeField->begin();

        $this->assertEquals($expectedValue, $actualValue);
    }

    public function testEnd()
    {
        $expectedValue = '</div>';
        $actualValue = $this->activeField->end();

        $this->assertEquals($expectedValue, $actualValue);

        // other tag
        $expectedValue = "</article>";
        $this->activeField->options['tag'] = 'article';
        $actualValue = $this->activeField->end();

        $this->assertTrue($actualValue === $expectedValue);
    }

    public function testLabel()
    {
        $expectedValue = '<label class="form-label" for="modelmock-attributename">Attribute Name</label>';
        $this->activeField->label();

        $this->assertEquals($expectedValue, $this->activeField->parts['{label}']);

        // label = false
        $expectedValue = '';
        $this->activeField->label(false);

        $this->assertEquals($expectedValue, $this->activeField->parts['{label}']);

        // $label = 'Label Name'
        $label = 'Label Name';
        $expectedValue = <<<EOT
<label class="form-label" for="modelmock-attributename">{$label}</label>
EOT;
        $this->activeField->label($label);

        $this->assertEquals($expectedValue, $this->activeField->parts['{label}']);
    }

    public function testError()
    {
        $expectedValue = '<label class="form-label" for="modelmock-attributename">Attribute Name</label>';
        $this->activeField->label();

        $this->assertEquals($expectedValue, $this->activeField->parts['{label}']);

        // label = false
        $expectedValue = '';
        $this->activeField->label(false);

        $this->assertEquals($expectedValue, $this->activeField->parts['{label}']);

        // $label = 'Label Name'
        $label = 'Label Name';
        $expectedValue = <<<EOT
<label class="form-label" for="modelmock-attributename">{$label}</label>
EOT;
        $this->activeField->label($label);

        $this->assertEquals($expectedValue, $this->activeField->parts['{label}']);
    }

    public function testHint()
    {
        $expectedValue = '<div class="form-hint">Hint Content</div>';
        $this->activeField->hint('Hint Content');

        $this->assertEquals($expectedValue, $this->activeField->parts['{hint}']);
    }

    public function testInput()
    {
        $expectedValue = <<<EOD
<input type="password" id="modelmock-attributename" class="form-control" name="ModelMock[attributeName]" data-ng-model="form.values.attributeName">
EOD;
        $this->activeField->input("password");

        $this->assertEquals($expectedValue, $this->activeField->parts['{input}']);

        // with options
        $expectedValue = <<<EOD
<input type="password" id="modelmock-attributename" class="form-control" name="ModelMock[attributeName]" weird="value" data-ng-model="form.values.attributeName">
EOD;
        $this->activeField->input("password", ['weird' => 'value']);

        $this->assertEquals($expectedValue, $this->activeField->parts['{input}']);
    }

    public function testTextInput()
    {
        $expectedValue = <<<EOD
<input type="text" id="modelmock-attributename" class="form-control" name="ModelMock[attributeName]" data-ng-model="form.values.attributeName">
EOD;
        $this->activeField->textInput();
        $this->assertEquals($expectedValue, $this->activeField->parts['{input}']);
    }

    public function testHiddenInput()
    {
        $expectedValue = <<<EOD
<input type="hidden" id="modelmock-attributename" class="form-control" name="ModelMock[attributeName]" data-ng-model="form.values.attributeName">
EOD;
        $this->activeField->hiddenInput();
        $this->assertEquals($expectedValue, $this->activeField->parts['{input}']);
    }

    public function testListBox()
    {
        $expectedValue = <<<EOD
<input type="hidden" name="ModelMock[attributeName]" value=""><select id="modelmock-attributename" class="form-control" name="ModelMock[attributeName]" size="4">
<option value="1">Item One</option>
<option value="2">Item 2</option>
</select>
EOD;
        $this->activeField->listBox(["1" => "Item One", "2" => "Item 2"]);
        $this->assertEquals($expectedValue, $this->activeField->parts['{input}']);
    }
}

/**
 * Helper Classes
 */
class ActiveFieldExtend extends ActiveField
{
    public $enableClientValidation = false;

    public function __construct()
    {
    }
}

