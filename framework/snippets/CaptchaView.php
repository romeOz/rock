<?php
namespace rock\snippets;



use rock\base\Snippet;
use rock\captcha\Captcha;
use rock\di\Container;
use rock\helpers\FileHelper;
use rock\Rock;

/**
 * @see Captcha
 */
class CaptchaView extends Snippet
{
    /** @var  Captcha */
    protected $_captcha;

    public function init()
    {
        parent::init();
        $this->_captcha = Container::load('captcha');
    }

    public function get()
    {
        if (!$dataImage = $this->_captcha->get()) {
            return '#';
        }

        if ($dataImage['mime_type'] === 'image/x-png') {
            $ext = '.png';
        } elseif ($dataImage['mime_type'] === 'image/jpeg') {
            $ext = '.jpg';
        } else {
            $ext = '.gif';
        }

        $uniq = uniqid();
        $path = Rock::getAlias('@assets') . DS . 'cache' . DS . 'captcha' . DS . $uniq . $ext;

        if (FileHelper::create($path, $dataImage['image'])) {
            return Rock::getAlias('@web') . '/cache/captcha/' . $uniq . $ext;
        }

        return '#';
    }
}