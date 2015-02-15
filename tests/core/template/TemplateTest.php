<?php

namespace rockunit\core\template;


use rock\Rock;
use rockunit\core\template\controllers\TestController;

/**
 * @group template
 */
class TemplateTest extends \PHPUnit_Framework_TestCase
{
    public function testLink()
    {
        $template = Rock::$app->template;
        $class = TestController::className();
        // context
        $this->assertSame('/test/', $template->replace("[[~{$class}]]"));
    }
}