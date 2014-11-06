<?php
namespace rock\captcha;

use rock\base\ComponentsInterface;
use rock\base\ComponentsTrait;
use rock\helpers\Helper;
use rock\Rock;

/**
 * @author   Kruglov Sergei (fork by Romeo)
 * @link     http://captcha.ru, http://kruglov.ru
 */
class Captcha implements ComponentsInterface, CaptchaInterface
{
    use ComponentsTrait {
        ComponentsTrait::init as parentInit;
    }

    /**
     * Do not change without changing font files.
     *
     * @var string
     */
    public $alphabet = '0123456789abcdefghijklmnopqrstuvwxyz';
    /**
     * Symbols used to draw captcha.
     *
     * Example:
     *  "0123456789" digits
     *  "23456789abcdegkmnpqsuvxyz"; #alphabet without similar symbols (o=0, 1=l, i=j, t=f)
     *
     * @var string
     */
    public $allowedSymbols = '23456789abcdegikpqsvxyz';
    /**
     * Folder with fonts.
     *
     * @var string
     */
    public $fontsDir = 'fonts';
    /**
     * Captcha string length.
     * random 5 or 6 or 7
     *
     * @var int
     */
    public $length = 0;
    /**
     * Captcha image size (you do not need to change it, this parameters is optimal).
     *
     * @var int
     */
    public $width = 160;
    public $height = 80;
    /**
     * Symbol's vertical fluctuation amplitude.
     *
     * @var int
     */
    public $fluctuationAmplitude = 8;
    /**
     * Noise white.
     * `0` -  no white noise
     *
     * @var float
     */
    public $whiteNoiseDensity = 0;

    /**
     * Noise black.
     * `0` -   no black noise
     *
     * @var float
     */
    public $blackNoiseDensity = 0;
    /**
     * Increase safety by prevention of spaces between symbols.
     *
     * @var bool
     */
    public $noSpaces = true;
    /**
     * Show credits.
     * set to false to remove credits line. Credits adds 12 pixels to image height.
     *
     * @var bool
     */
    public $showCredits = false;
    /**
     * Text credit.
     *
     * if empty, HTTP_HOST will be shown
     *
     * @var string
     */
    public $textCredits = null;
    /**
     * CAPTCHA image colors (RGB, 0-255).
     *
     * ```php
     * $captcha->foregroundColor = array(0, 0, 0);
     * $captcha->backgroundColor = array(220, 230, 255);
     * ```
     *
     * @var array
     */
    public $foregroundColor = [];
    public $backgroundColor = [];
    /**
     * JPEG quality of CAPTCHA image (bigger is better quality, but larger file size).
     *
     * @var int
     */
    public $jpegQuality = 90;

    public $sessionName = 'captcha';
    /**
     * Code of captcha.
     *
     * @var string
     */
    protected $code;


    public function init()
    {
        $this->parentInit();
        $this->length = Helper::getValue(
            $this->length,
            mt_rand(5, 7)
        );
        $this->foregroundColor = Helper::getValue(
            $this->foregroundColor,
            [mt_rand(0, 80), mt_rand(0, 80), mt_rand(0, 80)]
        );
        $this->backgroundColor = Helper::getValue(
            $this->backgroundColor,
            [mt_rand(220, 255), mt_rand(220, 255), mt_rand(220, 255)]
        );
    }

    /**
     * Get data captcha.
     *
     * @param bool $session create session
     * @return array
     */
    public function get($session = true)
    {
        if (!$data = $this->generate($session)) {
            return [];
        }

        return $data;
    }

    /**
     * Generate captcha.
     *
     * @param bool $session
     * @return array
     */
    protected function generate($session = true)
    {
        $fonts = [];
        $fontsdir_absolute = dirname(__FILE__) . DS . $this->fontsDir;
        if ($handle = opendir($fontsdir_absolute)) {
            while (false !== ($file = readdir($handle))) {
                if (preg_match('/\.png$/i', $file)) {
                    $fonts[] = $fontsdir_absolute . DS . $file;
                }
            }
            closedir($handle);
        }
        $alphabet_length = strlen($this->alphabet);
        // generating random keystring
        do {
            while (true) {
                $this->code = null;
                for ($i = 0; $i < $this->length; ++$i) {
                    $this->code .= $this->allowedSymbols{mt_rand(0, strlen($this->allowedSymbols) - 1)};
                }
                if (!preg_match('/cp|cb|ck|c6|c9|rn|rm|mm|co|do|cl|db|qp|qb|dp|ww/', $this->code)) {
                    break;
                }
            }
            $font_file = $fonts[mt_rand(0, count($fonts) - 1)];
            $font = imagecreatefrompng($font_file);
            imagealphablending($font, true);
            $fontfile_width = imagesx($font);
            $fontfile_height = imagesy($font) - 1;
            $font_metrics = [];
            $symbol = 0;
            $reading_symbol = false;
            // loading font
            for (
                $i = 0;
                $i < $fontfile_width && $symbol < $alphabet_length;
                ++$i
            ) {
                $transparent = (imagecolorat($font, $i, 0) >> 24) == 127;
                if (empty($reading_symbol) && empty($transparent)) {
                    $font_metrics[$this->alphabet{$symbol}] = ['start' => $i];
                    $reading_symbol = true;
                    continue;
                } elseif (!empty($reading_symbol) && !empty($transparent)) {
                    $font_metrics[$this->alphabet{$symbol}]['end'] = $i;
                    $reading_symbol = false;
                    ++$symbol;
                    continue;
                }
            }
            $img = imagecreatetruecolor($this->width, $this->height);
            imagealphablending($img, true);
            $white = imagecolorallocate($img, 255, 255, 255);
            $black = imagecolorallocate($img, 0, 0, 0);
            imagefilledrectangle($img, 0, 0, $this->width - 1, $this->height - 1, $white);

            // draw text
            $x = 1;
            $odd = mt_rand(0, 1);
            if ($odd == 0) {
                $odd = -1;
            }
            for ($i = 0; $i < $this->length; ++$i) {
                $m = $font_metrics[$this->code{$i}];
                $y = (($i % 2) * $this->fluctuationAmplitude - $this->fluctuationAmplitude / 2) * $odd
                     + mt_rand(-round($this->fluctuationAmplitude / 3), round($this->fluctuationAmplitude / 3))
                     + ($this->height - $fontfile_height) / 2;
                if ($this->noSpaces === true) {
                    $shift = 0;
                    if ($i > 0) {
                        $shift = 10000;
                        for ($sy = 3; $sy < $fontfile_height - 10; $sy += 1) {
                            for ($sx = $m['start'] - 1; $sx < $m['end']; $sx += 1) {
                                $rgb = imagecolorat($font, $sx, $sy);
                                $opacity = $rgb >> 24;
                                if ($opacity < 127) {
                                    $left = $sx - $m['start'] + $x;
                                    $py = $sy + $y;
                                    if ($py > $this->height) {
                                        break;
                                    }
                                    for (
                                        $px = min($left, $this->width - 1);
                                        $px > $left - 200 && $px >= 0;
                                        $px -= 1
                                    ) {
                                        $color = imagecolorat($img, $px, $py) & 0xff;
                                        if ($color + $opacity < 170) { // 170 - threshold
                                            if ($shift > $left - $px) {
                                                $shift = $left - $px;
                                            }
                                            break;
                                        }
                                    }
                                    break;
                                }
                            }
                        }
                        if ($shift == 10000) {
                            $shift = mt_rand(4, 6);
                        }
                    }
                } else {
                    $shift = 1;
                }
                imagecopy($img, $font, $x - $shift, $y, $m['start'], 1, $m['end'] - $m['start'], $fontfile_height);
                $x += $m['end'] - $m['start'] - $shift;
            }
        } while ($x >= $this->width - 10); // while not fit in canvas


         // noise
        $white = imagecolorallocate($font, 255, 255, 255);
        $black = imagecolorallocate($font, 0, 0, 0);
        for (
            $i = 0;
            $i < (($this->height - 30) * $x) * $this->whiteNoiseDensity;
            ++$i
        ) {
            imagesetpixel($img, mt_rand(0, $x - 1), mt_rand(10, $this->height - 15), $white);
        }
        for (
            $i = 0;
            $i < (($this->height - 30) * $x) * $this->blackNoiseDensity;
            ++$i
        ) {
            imagesetpixel(
                $img,
                mt_rand(0, $x - 1),
                mt_rand(10, $this->height - 15),
                $black
            );
        }
        $center = $x / 2;
        // credits. To remove, see configuration file
        $img2 = imagecreatetruecolor(
            $this->width,
            $this->height + ($this->showCredits === true
                ? 12
                : 0)
        );
        $foreground = imagecolorallocate(
            $img2,
            $this->foregroundColor[0],
            $this->foregroundColor[1],
            $this->foregroundColor[2]
        );
        $background = imagecolorallocate(
            $img2,
            $this->backgroundColor[0],
            $this->backgroundColor[1],
            $this->backgroundColor[2]
        );
        imagefilledrectangle(
            $img2,
            0,
            0,
            $this->width - 1,
            $this->height - 1,
            $background
        );
        imagefilledrectangle(
            $img2,
            0,
            $this->height,
            $this->width - 1,
            $this->height + 12,
            $foreground
        );
        $credits = empty($credits)
            ? $_SERVER['HTTP_HOST']
            : $credits;
        imagestring(
            $img2,
            2,
            $this->width / 2 - imagefontwidth(2) * strlen($credits) / 2,
            $this->height - 2,
            $credits,
            $background
        );
        // periods
        $rand1 = mt_rand(750000, 1200000) / 10000000;
        $rand2 = mt_rand(750000, 1200000) / 10000000;
        $rand3 = mt_rand(750000, 1200000) / 10000000;
        $rand4 = mt_rand(750000, 1200000) / 10000000;
        // phases
        $rand5 = mt_rand(0, 31415926) / 10000000;
        $rand6 = mt_rand(0, 31415926) / 10000000;
        $rand7 = mt_rand(0, 31415926) / 10000000;
        $rand8 = mt_rand(0, 31415926) / 10000000;
        // amplitudes
        $rand9 = mt_rand(330, 420) / 110;
        $rand10 = mt_rand(330, 450) / 100;

        // wave distortion
        for ($x = 0; $x < $this->width; ++$x) {
            for ($y = 0; $y < $this->height; ++$y) {
                $sx
                    =
                    $x + (sin($x * $rand1 + $rand5) + sin($y * $rand3 + $rand6)) * $rand9 - $this->width / 2 + $center +
                    1;
                $sy = $y + (sin($x * $rand2 + $rand7) + sin($y * $rand4 + $rand8)) * $rand10;
                if (
                    $sx < 0 ||
                    $sy < 0 ||
                    $sx >= $this->width - 1 ||
                    $sy >= $this->height - 1
                ) {
                    continue;
                } else {
                    $color = imagecolorat($img, $sx, $sy) & 0xFF;
                    $color_x = imagecolorat($img, $sx + 1, $sy) & 0xFF;
                    $color_y = imagecolorat($img, $sx, $sy + 1) & 0xFF;
                    $color_xy = imagecolorat($img, $sx + 1, $sy + 1) & 0xFF;
                }
                if (
                    $color == 255 &&
                    $color_x == 255 &&
                    $color_y == 255 &&
                    $color_xy == 255
                ) {
                    continue;
                } elseif (
                    $color === 0 &&
                    $color_x === 0 &&
                    $color_y === 0 &&
                    $color_xy === 0
                ) {
                    $newred = $this->foregroundColor[0];
                    $newgreen = $this->foregroundColor[1];
                    $newblue = $this->foregroundColor[2];
                } else {
                    $frsx = $sx - floor($sx);
                    $frsy = $sy - floor($sy);
                    $frsx1 = 1 - $frsx;
                    $frsy1 = 1 - $frsy;
                    $newcolor = (
                        $color * $frsx1 * $frsy1 +
                        $color_x * $frsx * $frsy1 +
                        $color_y * $frsx1 * $frsy +
                        $color_xy * $frsx * $frsy
                    );
                    if ($newcolor > 255) {
                        $newcolor = 255;
                    }
                    $newcolor = $newcolor / 255;
                    $newcolor0 = 1 - $newcolor;
                    $newred = $newcolor0 * $this->foregroundColor[0] +
                              $newcolor * $this->backgroundColor[0];
                    $newgreen = $newcolor0 * $this->foregroundColor[1] +
                                $newcolor * $this->backgroundColor[1];
                    $newblue = $newcolor0 * $this->foregroundColor[2] +
                               $newcolor * $this->backgroundColor[2];
                }
                imagesetpixel($img2, $x, $y, imagecolorallocate($img2, $newred, $newgreen, $newblue));
            }
        }
        ob_start();
        if (function_exists('imagejpeg')) {
            $return['mimeType'] = 'image/jpeg';
            //header("Content-Type: image/jpeg");
            imagejpeg($img2, null, $this->jpegQuality);
        } elseif (function_exists('imagegif')) {
            $return['mimeType'] = 'image/gif';
            //header("Content-Type: image/gif");
            imagegif($img2);
        } elseif (function_exists('imagepng')) {
            $return['mimeType'] = 'image/x-png';
            //header("Content-Type: image/x-png");
            imagepng($img2);
        }
        $return['image'] = ob_get_clean();
        if (empty($return['image'])) {
            Rock::warning(CaptchaException::UNKNOWN_VAR, ['name' => '$return[\'image\']']);
            return [];
        }
        if ($session) {
            $this->createSession();
        }

        return $return;
    }

    /**
     * Create session.
     */
    public function createSession()
    {
        $this->Rock->session->setFlash($this->sessionName, $this->code, false);
    }

    /**
     * Display captcha.
     *
     * @param bool $session
     */
    public function display($session = true)
    {
        if (!$data = $this->generate($session)) {
            return;
        }
        $this->setHttpHeaders($data['mimeType']);
        echo $data['image'];
    }

    /**
     * Sets the HTTP headers needed by image response.
     *
     * @param string $mimeType
     */
    protected function setHttpHeaders($mimeType)
    {
        $this->Rock->response->getHeaders()
            ->set('Pragma', 'public')
            ->set('Expires', '0')
            ->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->set('Content-Transfer-Encoding', 'binary')
            ->set('Content-type', $mimeType);
    }

    /**
     * Get data-uri.
     *
     * @param bool $session create session
     * @return string
     */
    public function getDataUri($session = true)
    {
        return 'data:image/png;base64,' . $this->getBase64($session);
    }

    /**
     * Get base64.
     *
     * @param bool $session
     * @return bool|string
     */
    public function getBase64($session = true)
    {
        if (!$data = $this->generate($session)) {
            return false;
        }

        return base64_encode($data['image']);
    }

    /**
     * Exists session by code of captcha.
     *
     * @param string|null $name
     * @return bool
     */
    public function hasSession($name = null)
    {
        return $this->Rock->session->hasFlash(Helper::getValue($name, $this->sessionName));
    }

    /**
     * Get code of captcha.
     *
     * @param string|null $name
     * @return string|null
     */
    public function getSession($name = null)
    {
        return $this->Rock->session->getFlash(Helper::getValue($name, $this->sessionName));
    }

    /**
     * Remove session.
     *
     * @param string|null $name
     */
    public function removeSession($name = null)
    {
        $this->Rock->session->removeFlash(Helper::getValue($name, $this->sessionName));
    }

    /**
     * Get code of captcha.
     *
     * @return null|string
     */
    public function getCode()
    {
        return $this->code;
    }
}