<?php
namespace rock\snippets;



use rock\base\Snippet;
use rock\helpers\FileHelper;
use rock\Rock;

/**
 * @see Captcha
 */
class CaptchaView extends Snippet
{

    public function get()
    {
        if (!$dataImage = $this->Rock->captcha->get()) {
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