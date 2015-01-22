<?php

namespace rock\snippets;
use rock\base\Snippet;
use rock\di\Container;
use rock\helpers\Html;
use rock\image\ImageProvider;
use rock\image\ThumbInterface;

/**
 * Snippet "Thumb" 
 * @package rock\snippets\
 *
 * @see Thumb
 */
class Thumb extends Snippet implements ThumbInterface
{
    /**
     * Src to image.
     * @var string
     */
    public $src;
    /**
     * width
     * @var
     */
    public $w;
    /**
     * height
     * @var
     */
    public $h;
    /**
     * quality
     * @var
     */
    public $q;
    /**
     * attr "class"
     * @var
     */
    public $class;
    /**
     * attr "alt"
     * @var
     */
    public $alt;
    public $title;
    /** @var  string */
    public $dummy;
    public $const = 1;
    public $autoEscape = false;

    /** @var  ImageProvider */
    private $_imageProvider;

    public function init()
    {
        parent::init();
        $this->_imageProvider = Container::load('imageProvider');
    }

    public function get()
    {
        if (empty($this->src)) {
            if (empty($this->dummy)) {
                return '';
            }
            $this->src = $this->dummy;
        }

        $options = [
            'class' => $this->class,
            'alt' => $this->alt,
            'title' => $this->title,
        ];

        $src = $this->_imageProvider->get($this->src, $this->w, $this->h);

        if (!((int)$this->const & self::WITHOUT_WIDTH_HEIGHT)) {
            $options['width'] = $this->_imageProvider->width;
            $options['height'] = $this->_imageProvider->height;
        }

        return (int)$this->const & self::OUTPUT_IMG ? Html::img($src, $options) : $src;
    }
}