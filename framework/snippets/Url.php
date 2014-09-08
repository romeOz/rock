<?php
namespace rock\snippets;

use rock\base\Snippet;
use rock\Rock;
use rock\template\Template;
use rock\url\UrlInterface;

/**
 * Snippet "Url"
 *
 * Example:
 *
 * ```
 * [[Url
 *  ?url=`http://site.com/categories/?view=all`
 *  ?args=`{"page" : 1}`
 *  ?beginPath=`/parts`
 *  ?endPath=`/news/`
 *  ?anchor=`name`
 *  ?const=`32`
 * ]]
 * ```
 */
class Url extends Snippet implements UrlInterface
{
    /**
     * URL for formatting.
     * @var string|null
     */
    public $url;
    /**
     * URL-arguments for set.
     * @var array
     */
    public $args;
    /**
     * URL-arguments for adding.
     * @var array
     */
    public $addArgs;
    /**
     * Anchor for adding.
     * @var string
     */
    public $anchor;
    /**
     * String to begin of URL-path.
     * @var string
     */
    public $beginPath;
    /**
     * String to end of URL-path.
     * @var string
     */
    public $endPath;
    /**
     * The replacement data.
     * ```php
     * $replacement = [$search, $replace];
     * ```
     * @var array
     */
    public $replace;
    /**
     * URL-arguments for removing.
     * @var array
     */
    public $removeArgs;
    /**
     * Remove all URL-arguments.
     * @var bool
     */
    public $removeAllArgs;
    /**
     * Remove anchor.
     * @var bool
     */
    public $removeAnchor;
    /**
     * Adduce URL to: Absolute, Relative, HTTP, HTTPS.
     * @var int
     * @see UrlInterface
     */
    public $const;
    /**
     * Use self host.
     * @var bool
     */
    public $selfHost;
    /**
     * @inheritdoc
     */
    public $autoEscape = Template::STRIP_TAGS;

    /**
     * @inheritdoc
     */
    public function get()
    {
        /** @var \rock\url\Url $urlBuilder */
        $urlBuilder = Rock::factory($this->url, \rock\url\Url::className());
        if (isset($this->removeArgs)) {
            $urlBuilder->removeArgs($this->removeArgs);
        }
        if (isset($this->removeAllArgs)) {
            $urlBuilder->removeAllArgs();
        }
        if (isset($this->removeAnchor)) {
            $urlBuilder->removeAnchor();
        }
        if (isset($this->beginPath)) {
            $urlBuilder->addBeginPath($this->beginPath);
        }
        if (isset($this->endPath)) {
            $urlBuilder->addEndPath($this->endPath);
        }
        if (isset($this->replace)) {
            if (!isset($this->replace[1])) {
                $this->replace[1] = '';
            }
            list($search, $replace) = $this->replace;
            $urlBuilder->replacePath($search, $replace);
        }
        if (isset($this->args)) {
            $urlBuilder->setArgs($this->args);
        }
        if (isset($this->addArgs)) {
            $urlBuilder->addArgs($this->addArgs);
        }
        if (isset($this->anchor)) {
            $urlBuilder->addAnchor($this->anchor);
        }

        return $urlBuilder->get((int)$this->const, (bool)$this->selfHost);
    }
}