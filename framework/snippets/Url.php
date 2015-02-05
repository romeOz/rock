<?php
namespace rock\snippets;

use rock\di\Container;
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
     * Adding CSRF-token.
     * @var bool
     */
    public $csrf = false;
    /**
     * URL-arguments for set.
     * @var array
     */
    public $args = [];
    /**
     * URL-arguments for adding.
     * @var array
     */
    public $addArgs = [];
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
     * Adduce URL to: `\rock\url\UrlInterface::ABS`, `\rock\url\UrlInterface::HTTP`, `\rock\url\UrlInterface::HTTPS`.
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
    /** @var  \rock\csrf\CSRF */
    private $_csrf;

    public function init()
    {
        parent::init();
        $this->_csrf = Container::load('csrf');
    }

    /**
     * @inheritdoc
     */
    public function get()
    {
        $urlBuilder = \rock\url\Url::set($this->url);
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
        if ($this->csrf) {
            $this->addArgs[$this->_csrf->csrfParam] = $this->_csrf->get();
        }
        if (!empty($this->args)) {
            $urlBuilder->setArgs($this->args);
        }
        if (!empty($this->addArgs)) {
            $urlBuilder->addArgs($this->addArgs);
        }
        if (isset($this->anchor)) {
            $urlBuilder->addAnchor($this->anchor);
        }

        return $urlBuilder->get((int)$this->const, (bool)$this->selfHost);
    }
}