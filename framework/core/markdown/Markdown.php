<?php

namespace rock\markdown;


use cebe\markdown\MarkdownExtra;
use rock\base\ComponentsTrait;
use rock\helpers\Helper;
use rock\image\DataProvider;
use rock\Rock;

class Markdown extends MarkdownExtra
{
    use ComponentsTrait;
    /**
     * @var boolean whether to interpret newlines as `<br />`-tags.
     * This feature is useful for comments where newlines are often meant to be real new lines.
     */
    public $enableNewlines = false;
    public $users = [];
    public $handlerLinkByUsername;
    public $denyTags = [];
    public $customAttributesTags = [
        'a' => [
            'rel' => 'nofollow'
        ]
    ];
    public $enabledDummy = false;
    public $specialAttributesDummy = '.dummy-video';
    public $imgDummy = '/assets/ico/play.png';
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

    protected function inlineMarkers()
    {
        return parent::inlineMarkers() + [
            'http'  => 'parseUrl',
            'ftp'   => 'parseUrl',
            '@'   => 'parseUsernameLink',
        ];
    }


    protected function parseUsernameLink($markdown)
    {
        if (!preg_match('/@(?P<username>[^\s]+)/', $markdown, $matches)) {
            return $markdown;
        }
        $url = '#';
        if ($this->handlerLinkByUsername instanceof \Closure &&
            $_url = call_user_func($this->handlerLinkByUsername, $matches['username'], $this)
        ) {
            $url = $_url;
            $this->users[] = $matches['username'];
        }

        $url = htmlspecialchars($url, ENT_COMPAT | ENT_HTML401, 'UTF-8');
        $username = htmlspecialchars($matches['username'], ENT_COMPAT | ENT_HTML401, 'UTF-8');
        $markdown = str_replace("@{$matches['username']}", '<a href="'.$url.'" title="'.$username.'">@'.$username.'</a>', $markdown);
        return [
            $markdown,
            mb_strlen($markdown, 'UTF-8')
        ];
    }


    protected function renderCode($block)
    {
        return $this->isTag('code') ? parent::renderCode($block) : '';
    }


    protected function consumeUsernameLink($lines, $current)
    {
        $username =  ltrim($lines[$current], '@');
        $url = '#';
        if ($this->handlerLinkByUsername instanceof \Closure &&
            $_url = call_user_func($this->handlerLinkByUsername, $username, $this)
        ) {
            $url = $_url;
            $this->users[] = $username;
        }
        $block = [
            'type' => 'usernameLink',
            //'content' => [],
            'url' => $url,
            'title' => $username,
            'text' => $lines[$current]
        ];

        return [$block, $current];
    }

    protected function renderUsernameLink($block)
    {
        $block['url'] = htmlspecialchars($block['url'], ENT_COMPAT | ENT_HTML401, 'UTF-8');
        $block['title'] = htmlspecialchars($block['title'], ENT_COMPAT | ENT_HTML401, 'UTF-8');
        $block['text'] = htmlspecialchars($block['text'], ENT_COMPAT | ENT_HTML401, 'UTF-8');
        return '<a href="'.$block['url'].'" title="'.$block['title'].'">'.$block['text'].'</a>';
    }

    protected function identifyTable($lines, $current)
    {
        if (strpos($lines[$current], '|') !== false && preg_match('~|.*|~', $lines[$current]) && preg_match('~^[\s\|\:-]+$~', $lines[$current + 1])) {
            return true;
        }

        if (isset($lines[$current+1]) && strpos($lines[$current], '{') !== false  && strpos($lines[$current+1], '|') !== false && preg_match('~|.*|~', $lines[$current+1]) && preg_match('~^[\s\|\:-]+$~', $lines[$current + 2])) {
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
            $tds = "<$this->_tableCellTag$align>" . $this->parseInline($row) . "</$this->_tableCellTag>";
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

    protected function parseTd($markdown)
    {
        if ($this->context[1] === 'table') {
            $align = empty($this->_tableCellAlign[$this->_tableCellCount]) ? '' : ' align="' . $this->_tableCellAlign[$this->_tableCellCount++] . '"';
            return ["</$this->_tableCellTag><$this->_tableCellTag$align>", isset($markdown[1]) && $markdown[1] === ' ' ? 2 : 1];
        }
        return [$markdown[0], 1];
    }


    /**
     * Parses a link indicated by `[`.
     */
    protected function parseLink($markdown)
    {
        if (!in_array('parseLink', array_slice($this->context, 1)) && ($parts = $this->parseLinkOrImage($markdown)) !== false) {
            list($text, $url, $title, $offset, $refKey, $data) = $parts;

            $attributes = '';
            $specialAttributes = [];
            if (isset($this->references[$refKey]['attributes'])) {
                $specialAttributes[] = $this->references[$refKey]['attributes'];
            }
            if (!empty($data['special'])) {
                $specialAttributes[] = $data['special'];
            }
            if (isset($markdown[$offset]) && $markdown[$offset] === '{' && preg_match("~^$this->_specialAttributesRegex~", substr($markdown, $offset), $matches)) {
                $attributes = $matches[1];
                $offset += strlen($matches[0]);
            }
            if (!empty($specialAttributes)) {
                $attributes = $this->renderAttributes(['attributes' => implode(' ', $specialAttributes)]);
            }
            if (!empty($this->customAttributesTags['a'])) {
                $attributes = implode(' ', [$this->renderOtherAttributes($this->customAttributesTags['a']), $attributes]);
            }

            $link = '<a href="' . htmlspecialchars($url, ENT_COMPAT | ENT_HTML401, 'UTF-8') . '"'
                    . (empty($title) ? '' : ' title="' . htmlspecialchars($title, ENT_COMPAT | ENT_HTML401, 'UTF-8') . '"')
                    . $attributes . '>' . $this->parseInline($text) . '</a>';

            return [$link, $offset];
        } else {
            return parent::parseLink($markdown);
        }
    }

    /**
     * Parses an image indicated by `![`.
     */
    protected function parseImage($markdown)
    {
        if (($parts = $this->parseLinkOrImage(substr($markdown, 1))) !== false) {
            list($text, $url, $title, $offset, $refKey, $data) = $parts;

            $specialAttributes = [];
            $attributes = '';
            if (isset($this->references[$refKey]['attributes'])) {
                $specialAttributes[] = $this->references[$refKey]['attributes'];
            }
            if (!empty($data['special'])) {
                $specialAttributes[] = $data['special'];
            }
            if (isset($markdown[$offset + 1]) && $markdown[$offset + 1] === '{' && preg_match("~^$this->_specialAttributesRegex~", substr($markdown, $offset + 1), $matches)) {
                $specialAttributes[] = $matches[1];
                $offset += strlen($matches[0]);
            }
            if (!empty($specialAttributes)) {
                $attributes = $this->renderAttributes(['attributes' => implode(' ', $specialAttributes)]);
            }
            if (isset($data['macros'])) {
                if ($this->isTag('thumb') && $data['macros'] === 'thumb' && isset($data['width'])) {
                    $url = $this->dataImage->get( '/' . ltrim($url, '/'), $data['width'], $data['height']);
                } elseif ($this->isTag('video') && $data['macros'] !== 'thumb') {
                    $video = $this->parseVideo(
                        $data['macros'],
                        $url,
                        Helper::getValue($data['width'], $this->defaultWidthVideo),
                        Helper::getValue($data['height'], $this->defaultHeightVideo),
                        Helper::getValue($title),
                        $specialAttributes
                    );

                    return [$video, $offset + 1];
                }
            }
            $image = '<img src="' . htmlspecialchars($url, ENT_COMPAT | ENT_HTML401, 'UTF-8') . '"'
                     . ' alt="' . htmlspecialchars($text, ENT_COMPAT | ENT_HTML401, 'UTF-8') . '"'
                     . (empty($title) ? '' : ' title="' . htmlspecialchars($title, ENT_COMPAT | ENT_HTML401, 'UTF-8') . '"')
                     . $attributes . ($this->html5 ? '>' : ' />');

            return [$image, $offset + 1];
        } else {
            return parent::parseImage($markdown);
        }
    }


    protected function parseVideo($hosting, $url, $width, $height, $title, array $specialAttributes)
    {
        switch ($hosting) {
            case 'youtube':
                $url = $this->enabledDummy === true ? "https://www.youtube.com/watch?v={$url}" : "//youtube.com/embed/{$url}/";
                break;
            case 'vimeo':
                $url = $this->enabledDummy === true ? "http://vimeo.com/{$url}" : "//player.vimeo.com/video/{$url}";
                break;
            case 'rutube':
                $url = $this->enabledDummy === true ? "http://rutube.ru/video/{$url}/": "//rutube.ru/play/embed/{$url}";
                break;
            case 'vk':
                $url = $this->enabledDummy === true ? "https://vk.com/video_ext.php?{$url}" :"//vk.com/video_ext.php?{$url}";
                break;
            case 'ivi':
                $url = $this->enabledDummy === true ? "http://www.ivi.ru/watch/{$url}" : "//ivi.ru/external/stub/?videoId={$url}";
                break;
            case 'dailymotion':
                $url = $this->enabledDummy === true ? "http://www.dailymotion.com/embed/video/{$url}" : "//dailymotion.com/embed/video/{$url}";
                break;
            case 'sapo':
                $url = $this->enabledDummy === true ? "http://rd3.videos.sapo.pt/{$url}" : "http://videos.sapo.pt/playhtml?file=http://rd3.videos.sapo.pt/{$url}/mov/1";
                break;
            default:
                throw new Exception(Exception::CRITICAL, Exception::UNKNOWN_HOSTING, ['name' => $hosting]);
        }

        if ($this->enabledDummy === true) {

            $attributes = [
                'style'=> "width: {$width}px; height: {$height}px",
                'target' => '_blank',
                'rel' => 'nofollow'
            ];
            $specialAttributes[] = $this->specialAttributesDummy;
            $specialAttributes = implode(' ', $specialAttributes);
            if (!empty($title)) {
                $attributes['title'] = $title;
            }
            $attributes = implode(' ', [$this->renderOtherAttributes($attributes), $this->renderAttributes(['attributes' => $specialAttributes])]);
            return '<a href="'.htmlspecialchars($url, ENT_COMPAT | ENT_HTML401, 'UTF-8').'"'.$attributes.'><img src="'.htmlspecialchars($this->imgDummy, ENT_COMPAT | ENT_HTML401, 'UTF-8').'"'
                     . ($this->html5 ? '>' : ' />') . '</a>';
        }
        $specialAttributes = implode(' ', $specialAttributes);
        $attributes = [
            'frameborder' =>0,
            'allowfullscreen' => 'allowfullscreen',
            'width' => $width,
            'height' => $height
        ];
        if (!empty($title)) {
            $attributes['title'] = $title;
        }
        $attributes = implode(' ', [$this->renderOtherAttributes($attributes), $this->renderAttributes(['attributes' => $specialAttributes])]);

        return '<iframe src="'.$url.'" '.$attributes.'></iframe>';
    }

    protected function renderOtherAttributes(array $attributes)
    {
        $result = '';
        foreach ($attributes as $name => $value) {
            $result .= " {$name}=\"$value\"";
        }

        return  $result;
    }

    protected function parseLinkOrImage($markdown)
    {
        if (($markdown = parent::parseLinkOrImage($markdown)) === false) {
            return false;
        }
        list($text, $url, $title, $offset, $key) = $markdown;
        $specialAttributes  = [];
        if (strpos($text, '{') !== false && preg_match("/{$this->_specialAttributesRegex}/", $text, $matches)) {
            $text = trim(str_replace($matches[0],'', $text));
        }
        if (isset($matches[1])) {
            $specialAttributes['special'] = $matches[1];
        }
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
     * Parses urls and adds auto linking feature.
     */
    protected function parseUrl($markdown)
    {
        $pattern = <<<REGEXP
			/(?(R) # in case of recursion match parentheses
				 \(((?>[^\s()]+)|(?R))*\)
			|      # else match a link with title
				^(https?|ftp):\/\/(([^\s()]+)|(?R))+(?<![\.,:;\'"!\?\s])
			)/x
REGEXP;

        if (!in_array('parseLink', $this->context) && preg_match($pattern, $markdown, $matches)) {
            $href = htmlspecialchars($matches[0], ENT_COMPAT | ENT_HTML401, 'UTF-8');
            $text = htmlspecialchars(urldecode($matches[0]), ENT_NOQUOTES, 'UTF-8');
            return [
                "<a href=\"$href\">$text</a>",
                strlen($matches[0])
            ];
        }
        return [substr($markdown, 0, 4), 4];
    }

    /**
     * @inheritdoc
     *
     * Parses a newline indicated by two spaces on the end of a markdown line.
     */
    protected function parsePlainText($text)
    {
        if ($this->enableNewlines) {
            return preg_replace("/(  \n|\n)/", $this->html5 ? "<br>\n" : "<br />\n", $text);
        } else {
            return parent::parsePlainText($text);
        }
    }

    protected function isTag($tag)
    {
        return !array_key_exists($tag, array_flip($this->denyTags));
    }
} 