<?php

namespace rock\snippets;
use rock\base\Snippet;
use rock\helpers\Html;
use rock\image\ThumbInterface;
use rock\Rock;

/**
 * Snippet "Thumb" 
 * @package rock\snippets\
 *
 * @see Thumb
 */
class Thumb extends Snippet implements ThumbInterface
{

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

    public $const;

    public $autoEscape = false;
    

    public function get()
    {
        if (empty($this->src)) {
            return '';
        }

        $options = [
            'class' => $this->class,
            'alt' => $this->alt,
            'title' => $this->title,
        ];


        $dataImage = Rock::$app->dataImage;
        $src = $dataImage->get($this->src, $this->w, $this->h);

        if (!((int)$this->const & self::WITHOUT_WIDTH_HEIGHT)) {
            $options['width'] = $dataImage->width;
            $options['height'] = $dataImage->height;
        }

        return Html::img($src, $options);
    }
}