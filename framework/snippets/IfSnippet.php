<?php
namespace rock\snippets;

use rock\di\Container;
use rock\execute\Execute;
use rock\helpers\Helper;
use rock\helpers\StringHelper;
use rock\template\Snippet;

/**
 * Snippet "IfSnippet"
 *
 * [[If
 *      ?subject=`:foo > 1 && :foo < 3`
 *      ?operands=`{"foo" : "[[+foo]]"}`
 *      ?then=`success`
 *      ?else=`fail`
 * ]]
 */
class IfSnippet extends Snippet
{
    /**
     * Condition (strip html/php-tags). E.g `:foo > 1 && :foo < 3`
     * @var string
     */
    public $subject;
    /**
     * Compliance of the operand to the placeholder. E.g. `{"foo" : "[[+foo]]"}`
     * @var array
     */
    public $operands = [];
    /** @var  string */
    public $then;
    /** @var  string */
    public $else;
    /**
     * Adding external placeholders in `tpl` and `wrapperTpl`.
     * @var array
     */
    public $addPlaceholders = [];
    /** @var  Execute */
    protected $execute;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->execute = Container::load('execute');
    }

    public function get()
    {
        if (!isset($this->subject, $this->operands, $this->then) ||
            empty($this->operands)) {
            return null;
        }
        $operands = $this->operands;
        $this->template->addMultiPlaceholders($this->template->calculateAddPlaceholders($this->addPlaceholders));
        $paramsTpl = [
            'subject'   => $this->subject,
            'params'    => $operands,
            'then'      => $this->then,
            'template' => $this->template
        ];

        if (isset($this->else)) {
            $paramsTpl['else'] = $this->else;
        }
        $data = [];
        $this->subject = strip_tags($this->subject);
        foreach ($operands as $keyParam => $valueParam) {
            $valueParam = Helper::toType($valueParam);
            if (is_string($valueParam)) {
                $valueParam = addslashes($valueParam);
            }
            $data[$keyParam] = $valueParam;
        }

        $value = '
            $template = $params[\'template\'];
            if (' . preg_replace('/:([\\w]+)/', '$data[\'$1\']', $this->subject) . ') {
                return $template->replace($params[\'then\']);
            }' .
            (isset($this->else)
                ? ' else {return $template->replace($params[\'else\']);}'
                : null
            );

        return $this->execute->get(StringHelper::removeSpaces($value), $paramsTpl, $data);
    }
}