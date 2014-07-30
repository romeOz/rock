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
    public $url;
    /**
     * Set args.
     * @var array
     */
    public $args;
    /**
     * Adding args.
     * @var array
     */
    public $addArgs;
    /**
     * Adding anchor.
     * @var string
     */
    public $anchor;
    /**
     * Concat to begin URL.
     * @var string
     */
    public $beginPath;
    /**
     * Concat to end URL.
     * @var string
     */
    public $endPath;
    /**
     * Selective removing arguments.
     * @var array
     */
    public $removeArgs;
    /**
     * Removing all arguments.
     * @var bool
     */
    public $removeAllArgs;
    /**
     * Removing anchor.
     * @var bool
     */
    public $removeAnchor;
    /**
     * @var int
     * @see UrlInterface
     */
    public $const;
    /**
     * Self host
     * @var bool
     */
    public $selfHost;

    public $autoEscape = Template::STRIP_TAGS;


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