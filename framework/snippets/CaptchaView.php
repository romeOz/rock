<?php
namespace rock\snippets;



use rock\base\Snippet;
use rock\helpers\File;
use rock\Rock;

/**
 * @see Captcha
 */
class CaptchaView extends Snippet
{

    public function get()
    {
        if (($dataImage = $this->Rock->captcha->get()) === false) {

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

        if (File::create($path, $dataImage['image'])) {

            $this->Rock->captcha->setSession();
            return Rock::getAlias('@web') . '/cache/captcha/' . $uniq . $ext;
        }

        return '#';
    }
}