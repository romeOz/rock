<?php

namespace rock\markdown;


use cebe\markdown\MarkdownExtra;
use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\helpers\Helper;
use rock\image\DataProvider;
use rock\Rock;

class Markdown extends MarkdownExtra implements ObjectInterface
{
    use ObjectTrait;

    const DUMMY = 1;
    const DUMMY_PLAY = 2;
    const DUMMY_PLAY_IN_MODAL = 4;

    /**
     * @var bool whether to interpret newlines as `<br>`-tags.
     * This feature is useful for comments where newlines are often meant to be real new lines.
     */
    public $enableNewlines = false;
    public $users = [];
    public $handlerLinkByUsername;
    public $denyTags = [];
    public $defaultAttributes = [
        'link' => [
            'rel' => 'nofollow',
            'target' => '_blank'
        ]
    ];
    /**
     * Enabled Dummy for video as:
     *
     * - {@see \rock\markdown\Markdown::DUMMY_VIDEO} - link ```<a href ="..." target="_blank">...</a>```
     * - {@see \rock\markdown\Markdown::DUMMY_PLAY} - when clicking on the link shows video (JavaScript)
     * - {@see \rock\markdown\Markdown::DUMMY_PLAY_IN_MODAL} - when clicking on the link shows modal window with video (JavaScript)
     *
     * @var int
     */
    public $dummy = 0;

    public $specialAttributesDummy = '.dummy-video';
    public $defaultWidthVideo = 560;
    public $defaultHeightVideo = 315;

    /** @var string|DataProvider  */
    public $dataImage = 'dataImage';


    private $_specialAttributesRegex = '\{((?:[#\.][\\w-]+\\s*)+)\}';

    private $_tableCellTag = 'td';
    private $_tableCellCount = 0;
    private $_tableCellAlign = [];

    public function init()
    {
        if (is_string($this->dataImage)) {
            $this->dataImage = Rock::factory($this->dataImage);
        }
    }

    public function parse($text)
    {
        return trim(parent::parse($text));
    }


    protected function renderCode($block)
    {
        return $this->isTag('code') ? parent::renderCode($block) : '';
    }


    /**
     * @marker @
     */
    protected function parseUsernameLink($markdown)
    {
        if (preg_match('/@(?P<username>[^\s\.\,]+)/', $markdown, $matches)) {

            return [
                // return the parsed tag as an element of the abstract syntax tree and call `parseInline()` to allow
                // other inline markdown elements inside this tag
                ['username', $this->parseInline($matches[1])],
                // return the offset of the parsed text
                strlen($matches[0])
            ];
        }

        return [['text', '@'], 1];
    }

    protected function renderUsername($element)
    {
        $username = $this->renderAbsy($element[1]);
        $url = '';
        if ($this->handlerLinkByUsername instanceof \Closure &&
            ($url = call_user_func($this->handlerLinkByUsername, $username, $this))) {
            $this->users[] = $username;
        }

        $url = $url ? htmlspecialchars($url, ENT_COMPAT | ENT_HTML401, 'UTF-8') : '#';
        $username = htmlspecialchars($username, ENT_COMPAT | ENT_HTML401, 'UTF-8');
        return '<a href="'.$url.'" title="'.$username.'">@'.$username.'</a>';
    }

    protected function identifyTable($line, $lines, $current)
    {
        if (strpos($line, '|') !== false && preg_match('~|.*|~', $line) && preg_match('~^[\s\|\:-]+$~', $lines[$current + 1])) {
            return true;
        }
        if (isset($lines[$current+1]) && strpos($line, '{') !== false  && strpos($lines[$current+1], '|') !== false && preg_match('~|.*|~', $lines[$current+1]) && preg_match('~^[\s\|\:-]+$~', $lines[$current + 2])) {
            return true;
        }
        return false;
    }

    /**
     * Consume lines for a table
     */
    protected function consumeTable($lines, $current)
    {

        if (isset($lines[$current]) &&
            strpos($lines[$current], '{') !== false &&
            preg_match("/{$this->_specialAttributesRegex}/", $lines[$current], $matches)
        ) {
            $attributes = $matches[1];
            ++$current;
        }

        list($block, $current) = parent::consumeTable($lines, $current);
        if (!empty($attributes)) {
            $block['attributes'] = $attributes;
        }

        return [$block, $current];
    }

    /**
     * Consume lines for a table
     */
    protected function renderTable($block)
    {
        if (!$this->isTag('table')) {
            return '';
        }
        $content = '';
        $this->_tableCellAlign = $block['cols'];
        $content .= "<thead>\n";
        $first = true;
        foreach($block['rows'] as $row) {
            $this->_tableCellTag = $first ? 'th' : 'td';
            $align = empty($this->_tableCellAlign[$this->_tableCellCount]) ? '' : ' align="' . $this->_tableCellAlign[$this->_tableCellCount++] . '"';
            $tds = "<$this->_tableCellTag$align>" . $this->renderAbsy($this->parseInline($row)) . "</$this->_tableCellTag>";
            $content .= "<tr>$tds</tr>\n";
            if ($first) {
                $content .= "</thead>\n<tbody>\n";
            }
            $first = false;
            $this->_tableCellCount = 0;
        }
        $attributes = '';
        if (!empty($block['attributes'])) {
            $attributes = $this->renderAttributes($block);
        }

        return "<table{$attributes}>\n$content</tbody>\n</table>";

    }

    /**
     * @marker |
     */
    protected function parseTd($markdown)
    {
        if (isset($this->context[1]) && $this->context[1] === 'table') {
            $align = empty($this->_tableCellAlign[$this->_tableCellCount]) ? '' : ' align="' . $this->_tableCellAlign[$this->_tableCellCount++] . '"';
            return [['text', "</$this->_tableCellTag><$this->_tableCellTag$align>"], isset($markdown[1]) && $markdown[1] === ' ' ? 2 : 1];
        }
        return [['text', $markdown[0]], 1];
    }


    protected function renderLink($block)
    {
        if (isset($block['refkey'])) {
            if (($ref = $this->lookupReference($block['refkey'])) !== false) {
                $block = array_merge($block, $ref);
            } else {
                return $block['orig'];
            }
        }
        $defaultAttributes = '';
        if (!empty($this->defaultAttributes['link'])) {
            $block = $this->concatSpecialAttributes($block, $this->defaultAttributes['link']);
            $defaultAttributes = $this->renderOtherAttributes($this->defaultAttributes['link']);
        }
        $attributes = $this->renderAttributes($block);
        return '<a href="' . htmlspecialchars($block['url'], ENT_COMPAT | ENT_HTML401, 'UTF-8') . '"'
               . (empty($block['title']) ? '' : ' title="' . htmlspecialchars($block['title'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE, 'UTF-8') . '"')
               . $attributes
               . (!empty($defaultAttributes) ? ' ' . $defaultAttributes : '')
               .'>' . (!empty($block['text']) ? $this->renderAbsy($block['text']) : '') . '</a>';
    }

    /**
     * Parses an image indicated by `![`.
     * @marker ![
     */
    protected function parseImage($markdown)
    {
        if (($parts = $this->parseLinkOrImage(substr($markdown, 1))) !== false) {
            list($text, $url, $title, $offset, $key, $data) = $parts;
            if (isset($data['macros'])) {
                if ($this->isTag('thumb') && $data['macros'] === 'thumb' && isset($data['width'])) {
                    $url = $this->dataImage->get( '/' . ltrim($url, '/'), $data['width'], $data['height']);
                } elseif ($this->isTag('video') && $data['macros'] !== 'thumb') {
                    $video = $this->calculateVideo(
                        $url,
                        Helper::getValue($data['width'], $this->defaultWidthVideo),
                        Helper::getValue($data['height'], $this->defaultHeightVideo),
                        Helper::getValue($title)

                    );
                    //return [['text', $video], $offset + 1];
                    $video['refkey'] = $key;
                    $video['orig'] = substr($markdown, 0, $offset + 1);
                    $video['hosting'] = $data['macros'];
                    return [
                        $video,
                        $offset + 1
                    ];
                }
            }
            return [
                [
                    'image',
                    'text' => $text,
                    'url' => $url,
                    'title' => $title,
                    'refkey' => $key,
                    'orig' => substr($markdown, 0, $offset + 1),
                ],
                $offset + 1
            ];
        } else {
            // remove all starting [ markers to avoid next one to be parsed as link
            $result = '!';
            $i = 1;
            while (isset($markdown[$i]) && $markdown[$i] == '[') {
                $result .= '[';
                $i++;
            }
            return [['text', $result], $i];
        }
    }


    protected function calculateVideo($url, $width, $height, $title)
    {
        if ($this->dummy & self::DUMMY) {
            return [
                'a',
                'text' => '',
                'url' => $url,
                'title' => $title,
                'width' => $width,
                'height' => $height,
            ];
        }

        return [
            'iframe',
            'text' => '',
            'url' => $url,
            'title' => $title,
            'width' => $width,
            'height' => $height
        ];
    }

    protected function renderIframe($block)
    {
        if (isset($block['refkey'])) {
            if (($ref = $this->lookupReference($block['refkey'])) !== false) {
                $block = array_merge($block, $ref);
            } else {
                return $block['orig'];
            }
        }
        if (empty($block['hosting'])) {
            $block['hosting'] = '';
        }
        list($block['url']) = $this->getHostingUrl($block['hosting'], $block['url']);
        $attributes = $this->renderAttributes($block);
        return '<iframe src="' . htmlspecialchars($block['url'], ENT_COMPAT | ENT_HTML401, 'UTF-8') . '"'
               . (empty($block['title']) ? '' : ' title="' . htmlspecialchars($block['title'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE, 'UTF-8') . '"')
               . ' width="'.$block['width'].'"'
               . ' height="'.$block['height'].'"'
               . ' allowfullscreen="allowfullscreen"'
               . ' frameborder="0"'
               . $attributes . '></iframe>';
    }

    protected function renderA($block)
    {
        if (isset($block['refkey'])) {
            if (($ref = $this->lookupReference($block['refkey'])) !== false) {
                $block = array_merge($block, $ref);
            } else {
                return $block['orig'];
            }
        }
        if (empty($block['attributes'])) {
            $block['attributes'] = '';
        }
        if (empty($block['hosting'])) {
            $block['hosting'] = '';
        }
        list($block['url'], $src) = $this->getHostingUrl($block['hosting'], $block['url']);
        $block['attributes'] = "{$this->specialAttributesDummy} " . $block['attributes'];
        $attributes = $this->renderAttributes($block);
        $title = htmlspecialchars($block['title'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE, 'UTF-8');
        $playVideo = $this->clientPlayVideo($src, $block['width'], $block['height'], $title);
        return '<a href="' . htmlspecialchars($block['url'], ENT_COMPAT | ENT_HTML401, 'UTF-8') . '"'
               . (empty($block['title']) ? '' : ' title="' . $title . '"')
               . ' style="width: '.$block['width'].'px; height: '.$block['height'].'px" target="_blank" rel="nofollow" '.$attributes.' '.$playVideo.'></a>';
    }

    protected function clientPlayVideo($src, $width, $height, $title)
    {
        if ($this->dummy & self::DUMMY_PLAY) {
            return 'data-ng-click="rock.html.playVideo(\''.$src.'\', '.$width.', '.$height.', \''.$title.'\', $event)"';
        }
        if ($this->dummy & self::DUMMY_PLAY_IN_MODAL) {
            return 'data-ng-click="rock.html.playVideoModal(\''.$src.'\', '.$width.', '.$height.', \''.$title.'\', $event)"';
        }
        return '';
    }

    protected function getHostingUrl($hosting, $url)
    {
        switch ($hosting) {
            case 'youtube':
                $src = "//youtube.com/embed/{$url}/";
                $url = $this->dummy & self::DUMMY ? "https://www.youtube.com/watch?v={$url}" : $src;
                break;
            case 'vimeo':
                $src = "//player.vimeo.com/video/{$url}";
                $url = $this->dummy & self::DUMMY ? "http://vimeo.com/{$url}" : $src;
                break;
            case 'rutube':
                $src = "//rutube.ru/play/embed/{$url}";
                $url = $this->dummy & self::DUMMY ? "http://rutube.ru/video/{$url}/": $src;
                break;
            case 'vk':
                $src = "//vk.com/video_ext.php?{$url}";
                $url = $this->dummy & self::DUMMY ? "https://vk.com/video_ext.php?{$url}" : $src;
                break;
            case 'ivi':
                $src = "//ivi.ru/external/stub/?videoId={$url}";
                $url = $this->dummy & self::DUMMY ? "http://www.ivi.ru/watch/{$url}" : $src;
                break;
            case 'dailymotion':
                $src = "//dailymotion.com/embed/video/{$url}";
                $url = $this->dummy & self::DUMMY ? "http://www.dailymotion.com/embed/video/{$url}" : $src;
                break;
            case 'sapo':
                $src = "http://videos.sapo.pt/playhtml?file=http://rd3.videos.sapo.pt/{$url}/mov/1";
                $url = $this->dummy & self::DUMMY ? "http://rd3.videos.sapo.pt/{$url}" : $src;
                break;
            default:
                throw new MarkdownException(MarkdownException::UNKNOWN_HOSTING, ['name' => $hosting]);
        }
        return [$url, $src];
    }

    protected function renderOtherAttributes(array $attributes)
    {
        $result = '';
        foreach ($attributes as $name => $value) {
            $result .= " {$name}=\"$value\"";
        }

        return  $result;
    }

    protected function concatSpecialAttributes(array $block, &$defaultAttributes = null)
    {
        if (empty($defaultAttributes['specialAttributes'])) {
            return $block;
        }

        if (empty($block['attributes'])) {
            $block['attributes'] = '';
        }
        $block['attributes'] = "{$defaultAttributes['specialAttributes']} " . $block['attributes'];
        unset($defaultAttributes['specialAttributes']);
        return $block;
    }

    protected function parseLinkOrImage($markdown)
    {
        if (($markdown = parent::parseLinkOrImage($markdown)) === false) {
            return false;
        }
        list($text, $url, $title, $offset, $key) = $markdown;
        $specialAttributes  = [];
        if ($text[0] === ':') {
            if (preg_match('/:(?P<macros>thumb|youtube|vimeo|rutube|vk|dailymotion|sapo)/', $text, $matches)) {
                $text = str_replace(":{$matches['macros']}", '', $text);
                if ($this->isTag('thumb') || $this->isTag('video')) {
                    $specialAttributes['macros'] = $matches['macros'];
                }
                if (preg_match('/(?P<width>\\d+)x(?P<height>\\d+)/', $text, $matches)) {
                    $text = trim(str_replace($matches[0], '', $text));
                    if ($this->isTag('thumb') || $this->isTag('video')) {
                        $specialAttributes['width'] = $matches['width'];
                        $specialAttributes['height'] = $matches['height'];
                    }
                }
            }
        }

        return [$text, $url, $title, $offset, $key, $specialAttributes];
    }

    protected function renderAttributes($block)
    {
        if (!$this->isTag('class')) {
            return '';
        }
        return parent::renderAttributes($block);
    }

    /**
     * @inheritdoc
     *
     * Parses a newline indicated by two spaces on the end of a markdown line.
     */
    protected function renderText($text)
    {
        if ($this->enableNewlines) {
            return preg_replace("/(  \n|\n)/", $this->html5 ? "<br>\n" : "<br />\n", $text[1]);
        } else {
            return parent::renderText($text);
        }
    }

    protected function isTag($tag)
    {
        return !array_key_exists($tag, array_flip($this->denyTags));
    }


    protected function parseInline($text)
    {
        $elements = parent::parseInline($text);
        // merge special attribute elements to links and images as they are not part of the final absy later
        $relatedElement = null;
        foreach($elements as $i => $element) {
            if ($element[0] === 'link' || $element[0] === 'image' || $element[0] === 'iframe') {
                $relatedElement = $i;
            } elseif ($element[0] === 'specialAttributes') {
                if ($relatedElement !== null) {
                    $elements[$relatedElement]['attributes'] = $element[1];
                    unset($elements[$i]);
                }
                $relatedElement = null;
            } else {
                $relatedElement = null;
            }
        }
        return $elements;
    }
} 