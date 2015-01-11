<?php
namespace rock\snippets;
use rock\base\Snippet;
use rock\helpers\Helper;
use rock\helpers\StringHelper;

/**
 * Snippet "Formula"
 *
 * ```html
 * [[Formula
 *      ?subject=`:pageCurrent - 1`
 *      ?operands=`{"pageCurrent" : "[[+pageCurrent]]"}`
 * ]]
 * ```
 */
class Formula extends Snippet
{
    /**
     * Subject (strip html/php-tags). E.g `:pageCurrent - 1`
     * @var string
     */
    public $subject;
    public $operands;

    public function get()
    {
        if (!isset($this->subject) || (empty($this->operands))) {
            return null;
        }

        $data = [];
        $this->subject = strip_tags($this->subject);

        foreach ($this->operands as $keyParam => $valueParam) {
            $valueParam = Helper::toType($valueParam);
            if (is_string($valueParam)) {
                $valueParam = addslashes($valueParam);
            }
            $data[$keyParam] = $valueParam;
        }

        return $this->Rock->eval->get(
            StringHelper::removeSpaces('return ' . preg_replace('/:([\\w]+)/', '$data[\'$1\']', $this->subject) . ';'),
            [
                'subject'   => $this->subject,
                'operands'    => $this->operands
            ],
            $data
        );
    }
}