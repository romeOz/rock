<?php

namespace rock\url;

use rock\base\ComponentsTrait;
use rock\base\ObjectTrait;
use rock\helpers\Helper;
use rock\helpers\String;
use rock\Rock;

/**
 * Class Url
 * @property string $scheme
 * @property string $host
 * @property int $port
 * @property string $user
 * @property string $pass
 * @property string $path
 * @property string|null $query
 * @property string|null $fragment
 * @package rock\template\url
 */
class Url implements UrlInterface
{
    use ComponentsTrait {
        ComponentsTrait::__construct as parentConstruct;
    }

    /**
     * Array data by url
     *
     * @var array
     */
    protected $dataUrl = [];

    /**
     * Dummy by url
     *
     * @var string
     */
    public $dummy = '#';

    public $strip = true;

    public function __construct($url = null, $config = [])
    {
        $this->parentConstruct($config);
        $url = empty($url) ? $this->Rock->request->getAbsoluteUrl() : Rock::getAlias($url);
        $this->dataUrl = parse_url(trim($url));
        if (isset($this->dataUrl['query'])) {
            parse_str($this->dataUrl['query'], $this->dataUrl['query']);
        }
    }

    /**
     * Set args
     *
     * @param array $args - array args
     * @return $this
     */
    public function setArgs(array $args)
    {
        $this->dataUrl['query'] = $args;

        return $this;
    }

    /**
     * Add args
     *
     * @param array $args - array args
     * @return $this
     */
    public function addArgs(array $args)
    {
        $this->dataUrl['query'] = array_merge(Helper::getValue($this->dataUrl['query'], []), $args);
        $this->dataUrl['query'] = array_filter($this->dataUrl['query']);
        return $this;
    }


    /**
     * Remove args
     *
     * @param array $args - array args
     * @return $this
     */
    public function removeArgs(array $args)
    {
        if (empty($this->dataUrl['query'])) {
            return $this;
        }

        $this->dataUrl['query'] = array_diff_key(
            $this->dataUrl['query'],
            array_flip($args)
        );

        return $this;
    }

    public function removeAllArgs()
    {
        $this->dataUrl['query'] = null;
        return $this;
    }

    /**
     * Add args
     *
     * @param string $anchor
     * @return $this
     */
    public function addAnchor($anchor)
    {
        $this->dataUrl['fragment'] = $anchor;

        return $this;
    }

    /**
     * Remove anchor
     *
     * @return $this
     */
    public function removeAnchor()
    {
        $this->dataUrl['fragment'] = null;

        return $this;
    }


    /**
     * Adding string to begin of path
     *
     * @param string $value
     * @return $this
     */
    public function addBeginPath($value)
    {
        $this->dataUrl['path'] = $value . $this->dataUrl['path'];

        return $this;
    }


    /**
     * Adding string to end pf path
     *
     * @param string $value
     * @return $this
     */
    public function addEndPath($value)
    {
        $this->dataUrl['path'] .= $value;

        return $this;
    }

    public function callback(\Closure $callback)
    {
        call_user_func($callback, $this);
        return $this;
    }

    protected function build(array $data)
    {
        $url = String::rconcat($data['scheme'], '://');

        if (isset($data['user']) && isset($data['pass'])) {
            $url .= String::rconcat($data['user'], ':');
            $url .= String::rconcat($data['pass'], '@');
        }
        $url .= Helper::getValue($data['host']);
        $url .= preg_replace(['/\/+(?!http:\/\/)/', '/\\\+/'], '/', $data['path']);
        if (isset($data['query'])) {
            if (is_string($data['query'])) {
                $data['query'] = [$data['query']];
            }
            /**
             * @see http://php.net/manual/ru/function.http-build-query.php#111819
             */
            $url .= '?' . preg_replace('/%5B[0-9]+%5D/i', '%5B%5D', http_build_query($data['query']));
        }
        $url .= String::lconcat($data['fragment'], '#');

        return $url;
    }

    public function get($const = 0, $selfHost = false)
    {
        if (empty($this->dataUrl['path'])) {
            $this->dataUrl = array_merge(parse_url($this->Rock->request->getAbsoluteUrl()), $this->dataUrl);
        }
        if ($selfHost == true) {
            $this->dataUrl['scheme'] = $this->Rock->request->getScheme();
            $this->dataUrl['host'] = $this->Rock->request->getHost();
        }

        if ($const & self::HTTP && isset($this->dataUrl['host'])) {
            $this->dataUrl['scheme'] = 'http';
        } elseif ($const & self::HTTPS && isset($this->dataUrl['host'])) {
            $this->dataUrl['scheme'] = 'https';
        } elseif($const & self::ABS) {
            if (!isset($this->dataUrl['host'])) {
                $this->dataUrl['scheme'] = $this->Rock->request->getScheme();
                $this->dataUrl['host'] = $this->Rock->request->getHost();
            }
        } else {
            unset($this->dataUrl['scheme'] , $this->dataUrl['host'], $this->dataUrl['user'], $this->dataUrl['pass']);
        }
        return $this->strip === true ? strip_tags($this->build($this->dataUrl)) : $this->build($this->dataUrl);
    }

    /**
     * Set data of url
     * @param $name
     * @param $value
     *
     * ```php
     * (new Url())->host = site.com
     * ```
     */
    public function __set($name, $value)
    {
        $this->dataUrl[$name] = $value;
    }

    /**
     * Get data of url
     * @param $name
     * @return string|null
     *
     * ```php
     * echo (new Url())->host; // result: site.com
     * ```
     */
    public function __get($name)
    {
        if (isset($this->dataUrl[$name])) {
            return $this->dataUrl[$name];
        }

        return null;
    }

    /**
     * Get absolute url: http://site.com
     * @param bool $selfHost
     * @return null|string
     */
    public function getAbsoluteUrl($selfHost = false)
    {
        return $this->get(self::ABS, $selfHost);
    }

    /**
     * Get absolute url: /
     * @param bool $selfHost
     * @return null|string
     */
    public function getRelativeUrl($selfHost = false)
    {
        return $this->get(0, $selfHost);
    }

    /**
     * Get http url: http://site.com
     * @param bool $selfHost
     * @return null|string
     */
    public function getHttpUrl($selfHost = false)
    {
        return $this->get(self::HTTP, $selfHost);
    }

    /**
     * Get https url: http://site.com
     * @param bool $selfHost
     * @return null|string
     */
    public function getHttpsUrl($selfHost = false)
    {
        return $this->get(self::HTTPS, $selfHost);
    }
}