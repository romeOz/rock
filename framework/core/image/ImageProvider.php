<?php

namespace rock\image;

use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
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

    /** @var FileManager|\Closure */
    public static $adapterImage = '';
    /** @var FileManager|\Closure */
    public static $adapterCache = '';
    
    public function init()
    {
        $this->srcImage = Rock::getAlias($this->srcImage);
        $this->srcCache = Rock::getAlias($this->srcCache);
    }

    /**
     * @return FileManager
     * @throws ImageException
     */
    public function getAdapterImage()
    {
        if (static::$adapterImage instanceof FileManager) {
            return static::$adapterImage;
        }
        if (static::$adapterImage instanceof \Closure) {
            static::$adapterImage = call_user_func(static::$adapterImage, $this);
        } else {
            static::$adapterImage = Rock::factory(static::$adapterImage);
        }
        if (!static::$adapterImage instanceof FileManager) {
            throw new ImageException(ImageException::UNKNOWN_CLASS, ['class' => static::$adapterImage]);
        }
        return static::$adapterImage;
    }

    /**
     * @return FileManager
     * @throws ImageException
     */
    public function getAdapterCache()
    {
        if (static::$adapterCache instanceof FileManager) {
            return static::$adapterCache;
        }
        if (static::$adapterCache instanceof \Closure) {
            static::$adapterCache = call_user_func(static::$adapterCache, $this);
        } else {
            static::$adapterCache = Rock::factory(static::$adapterCache);
        }
        if (!static::$adapterCache instanceof FileManager) {
            throw new ImageException(ImageException::UNKNOWN_CLASS, ['class' => static::$adapterCache]);
        }

        return static::$adapterCache;
    }


    protected $resource;
    public function get($path, $width = null, $height = null)
    {
        $path = $this->preparePath($path);
        if (!$this->getAdapterImage()->has($path)) {
            return $this->srcImage . '/'. ltrim($path, '/');
        }

        $this->resource = $this->getAdapterImage()->readStream($path);

        if ((empty($width) && empty($height)) || empty(static::$adapterCache)) {
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


    protected $src;
    protected function prepareImage($path)
    {
        $metadata = $this->getAdapterImage()->getMetadata($path);
        $path = implode(DIRECTORY_SEPARATOR, [trim($metadata['dirname'], DIRECTORY_SEPARATOR), "{$this->width}x{$this->height}", $metadata['basename']]);
        $this->src = $this->srcCache . '/'. ltrim($path, '/');
        if ($this->getAdapterCache()->has($path)) {
            return;
        }
        if ($this->handler instanceof \Closure) {
            call_user_func($this->handler, $path, $this);
            return;
        }

        if (!$this->getAdapterCache()->write($path, Image::thumbnail($this->resource, $this->width, $this->height)->get('jpg'))) {
            new ImageException(ImageException::NOT_CREATE_FILE, ['path' => $path]);
        }
    }
}