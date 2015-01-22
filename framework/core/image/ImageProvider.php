<?php

namespace rock\image;

use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\di\Container;
use rock\file\FileManager;
use rock\imagine\Image;
use rock\Rock;

class ImageProvider implements ObjectInterface
{
    use ObjectTrait;

    public $width;
    public $height;
    public $maxFiles = 100;
    public $srcImage = '@web/images';
    public $srcCache = '@web/cache';
    public $handler;
    /** @var FileManager|array */
    public $adapterImage ;
    /** @var FileManager|array */
    public $adapterCache;
    protected $resource;
    protected $src;
    
    public function init()
    {
        $this->srcImage = Rock::getAlias($this->srcImage);
        $this->srcCache = Rock::getAlias($this->srcCache);

        if (!is_object($this->adapterImage)) {
            $this->adapterImage = Container::load($this->adapterImage);
        }

        if (!is_object($this->adapterCache)) {
            $this->adapterCache = Container::load($this->adapterCache);
        }
    }



    public function get($path, $width = null, $height = null)
    {
        $path = $this->preparePath($path);
        if (!$this->adapterImage->has($path)) {
            return $this->srcImage . '/'. ltrim($path, '/');
        }

        $this->resource = $this->adapterImage->readStream($path);

        if ((empty($width) && empty($height)) || empty($this->adapterCache)) {
            return $this->srcImage . '/'. ltrim($path, '/');
        }

        $this->calculateDimensions($width, $height);

        $this->prepareImage($path);

        return $this->src;
    }

    protected function preparePath($path)
    {
        if (empty($path)) {
            return '';
        }

        return str_replace($this->srcImage , '', $path);
    }


    protected function calculateDimensions($width = null, $height = null)
    {
        if (empty($width)) {
            $width = Image::getImagine()->read($this->resource)->getSize()->getWidth();
        }
        if (empty($height)) {
            $height = Image::getImagine()->read($this->resource)->getSize()->getHeight();
        }

        $this->width = $width;
        $this->height = $height;
    }

    protected function prepareImage($path)
    {
        $metadata = $this->adapterImage->getMetadata($path);
        $path = implode(DIRECTORY_SEPARATOR, [trim($metadata['dirname'], DIRECTORY_SEPARATOR), "{$this->width}x{$this->height}", $metadata['basename']]);
        $this->src = $this->srcCache . '/'. ltrim($path, '/');
        if ($this->adapterCache->has($path)) {
            return;
        }
        if ($this->handler instanceof \Closure) {
            call_user_func($this->handler, $path, $this);
            return;
        }

        if (!$this->adapterCache->write($path, Image::thumbnail($this->resource, $this->width, $this->height)->get('jpg'))) {
            new ImageException(ImageException::NOT_CREATE_FILE, ['path' => $path]);
        }
    }
}