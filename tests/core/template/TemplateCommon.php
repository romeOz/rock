<?php

namespace rockunit\core\template;


use rock\Rock;
use rock\template\Template;
use rockunit\common\CommonTestTrait;

abstract class TemplateCommon extends \PHPUnit_Framework_TestCase
{
    use CommonTestTrait;

    protected $path;

    protected $filters = [];
    protected $snippets = [];
    /** @var  Template */
    protected $template;

    abstract protected function calculatePath();

    protected function setUp()
    {
        parent::setUp();
        $this->calculatePath();
        Rock::setAlias('rockunit.tpl', $this->path);

        $this->template = Rock::$app->template;
        $this->template->autoEscape = Template::ESCAPE | Template::TO_TYPE;
        $this->template->removeAllPlaceholders(true);
        $this->template->removeAllResource();
    }

    public function removeSpace($value)
    {
        return preg_replace('/\\s+/', '', $value);
    }
} 