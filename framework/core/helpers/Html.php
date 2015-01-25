<?php

namespace rock\helpers;


use rock\csrf\CSRF;
use rock\di\Container;
use rock\request\Request;
use rock\url\Url;

class Html
{
    /**
     * @var array list of void elements (element name => 1)
     * @see http://www.w3.org/TR/html-markup/syntax.html#void-element
     */
    public static $voidElements = [
        'area' => 1,
        'base' => 1,
        'br' => 1,
        'col' => 1,
        'command' => 1,
        'embed' => 1,
        'hr' => 1,
        'img' => 1,
        'input' => 1,
        'keygen' => 1,
        'link' => 1,
        'meta' => 1,
        'param' => 1,
        'source' => 1,
        'track' => 1,
        'wbr' => 1,
    ];
    /**
     * @var array the preferred order of attributes in a tag. This mainly affects the order of the attributes
     * that are rendered by {@see \rock\helpers\Html::renderTagAttributes()}.
     */
    public static $attributeOrder = [
        'type',
        'id',
        'class',
        'name',
        'value',

        'href',
        'src',
        'action',
        'method',

        'selected',
        'checked',
        'readonly',
        'disabled',
        'multiple',

        'size',
        'maxlength',
        'width',
        'height',
        'rows',
        'cols',

        'alt',
        'title',
        'rel',
        'media',
    ];

    /**
     * Encodes special characters into HTML entities.
     *
     * The {@see \rock\Rock::$charset} will be used for encoding.
     *
     * @param string  $content      the content to be encoded
     * @param boolean $doubleEncode whether to encode HTML entities in `$content`. If false,
     *                              HTML entities in `$content` will not be further encoded.
     * @return string the encoded content
     * @see decode()
     * @see http://www.php.net/manual/en/function.htmlspecialchars.php
     */
    public static function encode($content, $doubleEncode = true)
    {
        return StringHelper::encode($content, $doubleEncode);
    }

    /**
     * Decodes special HTML entities back to the corresponding characters.
     *
     * This is the opposite of {@see \rock\helpers\Html::encode()}.
     *
     * @param string $content the content to be decoded
     * @return string the decoded content {@see \rock\helpers\Html::encode()}
     * @see http://www.php.net/manual/en/function.htmlspecialchars-decode.php
     */
    public static function decode($content)
    {
        return StringHelper::decode($content);
    }

    /**
     * Generates a complete HTML tag.
     *
     * @param string $name    the tag name
     * @param string $content the content to be enclosed between the start and end tags. It will not be HTML-encoded.
     *                        If this is coming from end users, you should consider {@see \rock\helpers\Html::encode()} it to prevent XSS attacks.
     * @param array  $options the HTML tag attributes (HTML options) in terms of name-value pairs.
     *                        These will be rendered as the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()}.
     *                        If a value is null, the corresponding attribute will not be rendered.
     *
     * For example when using `['class' => 'my-class', 'target' => '_blank', 'value' => null]` it will result in the
     * html attributes rendered like this: `class="my-class" target="_blank"`.
     *
     * See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return string the generated HTML tag
     * @see beginTag()
     * @see endTag()
     */
    public static function tag($name, $content = '', $options = [])
    {
        $html = "<$name" . static::renderTagAttributes($options) . '>';

        return isset(static::$voidElements[strtolower($name)]) ? $html : "$html$content</$name>";
    }

    /**
     * Generates a start tag.
     *
     * @param string $name    the tag name
     * @param array  $options the tag options in terms of name-value pairs. These will be rendered as
     *                        the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()}.
     *                        If a value is null, the corresponding attribute will not be rendered.
     *                        See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated start tag
     * @see endTag()
     * @see tag()
     */
    public static function beginTag($name, $options = [])
    {
        return "<$name" . static::renderTagAttributes($options) . '>';
    }

    /**
     * Generates an end tag.
     *
     * @param string $name the tag name
     * @return string the generated end tag
     * @see beginTag()
     * @see tag()
     */
    public static function endTag($name)
    {
        return "</$name>";
    }

    /**
     * Generates a style tag.
     *
     * @param string $content the style content
     * @param array  $options the tag options in terms of name-value pairs. These will be rendered as
     *                        the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()}.
     *                        If a value is null, the corresponding attribute will not be rendered.
     *                        If the options does not contain "type", a "type" attribute with value "text/css" will be used.
     *                        See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated style tag
     */
    public static function style($content, $options = [])
    {
        return static::tag('style', $content, $options);
    }

    /**
     * Generates a script tag.
     *
     * @param string $content the script content
     * @param array  $options the tag options in terms of name-value pairs. These will be rendered as
     *                        the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()} .
     *                        If a value is null, the corresponding attribute will not be rendered.
     *                        If the options does not contain "type", a "type" attribute with value "text/javascript" will be rendered.
     *                        See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated script tag
     */
    public static function script($content, $options = [])
    {
        return static::tag('script', $content, $options);
    }

    /**
     * Generates a link tag that refers to an external CSS file.
     *
     * @param array|string $url     the URL of the external CSS file. This parameter will be
     *                              processed by {@see \rock\url\Url::getAbsoluteUrl()}.
     * @param array        $options the tag options in terms of name-value pairs. The following option is specially handled:
     *
     * - condition: specifies the conditional comments for IE, e.g., `lt IE 9`. When this is specified,
     *   the generated `script` tag will be enclosed within the conditional comments. This is mainly useful
     *   for supporting old versions of IE browsers.
     *
     * The rest of the options will be rendered as the attributes of the resulting link tag. The values will
     * be HTML-encoded using {@see \rock\helpers\Html::encode()} . If a value is null, the corresponding attribute will not be rendered.
     * See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated link tag
     */
    public static function cssFile($url, $options = [])
    {
        if (!isset($options['rel'])) {
            $options['rel'] = 'stylesheet';
        }
        $urlBuilder = Url::set($url);
        $options['href'] = $urlBuilder->getAbsoluteUrl();
        if (isset($options['condition'])) {
            $condition = $options['condition'];
            unset($options['condition']);

            return "<!--[if $condition]>\n" . static::tag('link', '', $options) . "\n<![endif]-->";
        } elseif (isset($options['noscript']) && $options['noscript'] === true) {
            unset($options['noscript']);
            return "<noscript>" . static::tag('link', '', $options) . "</noscript>";
        } else {
            return static::tag('link', '', $options);
        }
    }

    /**
     * Generates a script tag that refers to an external JavaScript file.
     *
     * @param string $url     the URL of the external JavaScript file. This parameter will
     *                        be processed by {@see \rock\url\Url::getAbsoluteUrl()}.
     * @param array  $options the tag options in terms of name-value pairs. The following option is specially handled:
     *
     * - condition: specifies the conditional comments for IE, e.g., `lt IE 9`. When this is specified,
     *   the generated `script` tag will be enclosed within the conditional comments. This is mainly useful
     *   for supporting old versions of IE browsers.
     *
     * The rest of the options will be rendered as the attributes of the resulting script tag. The values will
     * be HTML-encoded using {@see \rock\helpers\Html::encode()}. If a value is null, the corresponding attribute will not be rendered.
     * See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated script tag
     */
    public static function jsFile($url, $options = [])
    {
        $urlBuilder = Url::set($url);
        $options['src'] = $urlBuilder->getAbsoluteUrl();
        if (isset($options['condition'])) {
            $condition = $options['condition'];
            unset($options['condition']);

            return "<!--[if $condition]>\n" . static::tag('script', '', $options) . "\n<![endif]-->";
        } else {
            return static::tag('script', '', $options);
        }
    }

    /**
     * Generates the meta tags containing CSRF token information.
     *
     * @return string the generated meta tags
     * @see \rock\csrf\CSRF::$enableCsrfValidation
     */
    public static function csrfMetaTags()
    {
        $csrf = static::getCSRF();
        if ($csrf instanceof CSRF && $csrf->enableCsrfValidation) {
            return static::tag('meta', '', ['name' => 'csrf-param', 'content' => $csrf->csrfParam]) . "\n    "
                   . static::tag('meta', '', ['name' => 'csrf-token', 'content' => $csrf->get()]) . "\n";
        } else {
            return '';
        }
    }

    /**
     * Generates a form start tag.
     *
     * @param array|string $action  the form action URL. This parameter
     *                              will be processed by {@see \rock\url\Url::getAbsoluteUrl()}.
     * @param string       $method  the form submission method, such as "post", "get", "put", "delete" (case-insensitive).
     *                              Since most browsers only support "post" and "get", if other methods are given, they will
     *                              be simulated using "post", and a hidden input will be added which contains the actual method type.
     *                              See {@see \rock\request\Request::$methodVar} for more details.
     * @param string|null  $name    name of form
     * @param array        $options the tag options in terms of name-value pairs. These will be rendered as
     *                              the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()}.
     *                              If a value is null, the corresponding attribute will not be rendered.
     *                              See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated form start tag.
     * @see endForm()
     */
    public static function beginForm($action = null, $method = 'post', $name = null, $options = [])
    {
        $action = Url::set($action)->getAbsoluteUrl();
        $hiddenInputs = [];

        $request = static::getRequest();
        if ($request instanceof Request) {

            if (strcasecmp($method, 'get') && strcasecmp($method, 'post')) {
                // simulate PUT, DELETE, etc. via POST
                $hiddenInputs[] = static::hiddenInput(
                    isset($name) ? $name . "[{$request->methodVar}]" : $request->methodVar,
                    $method,
                    ArrayHelper::getValue($options, 'hiddenMethod', [])
                );
                $method = 'post';
            }
        }

        $csrf = static::getCSRF();
        if ($csrf instanceof CSRF && $csrf->enableCsrfValidation && !strcasecmp($method, 'post')) {
            $token = $csrf->get();
            $hiddenInputs[] = static::hiddenInput(
                isset($name) ? "{$name}[_csrf]" : '_csrf',
                $token,
                ArrayHelper::getValue($options, 'hiddenCsrf', [])
            );
        }

        if (!strcasecmp($method, 'get') && ($pos = strpos($action, '?')) !== false) {
            // query parameters in the action are ignored for GET method
            // we use hidden fields to add them back
            foreach (explode('&', substr($action, $pos + 1)) as $pair) {
                if (($pos1 = strpos($pair, '=')) !== false) {
                    $hiddenInputs[] = static::hiddenInput(
                        urldecode(substr($pair, 0, $pos1)),
                        urldecode(substr($pair, $pos1 + 1))
                    );
                } else {
                    $hiddenInputs[] = static::hiddenInput(urldecode($pair), '');
                }
            }
            $action = substr($action, 0, $pos);
        }
        unset($options['hiddenMethod'], $options['hiddenCsrf']);
        $options['action'] = $action;
        $options['method'] = $method;
        $form = static::beginTag('form', $options);
        if (!empty($hiddenInputs)) {
            $form .= "\n" . implode("\n", $hiddenInputs);
        }

        return $form;
    }

    /**
     * Generates a form end tag.
     *
     * @return string the generated tag
     * @see beginForm()
     */
    public static function endForm()
    {
        return '</form>';
    }

    /**
     * Generates a hyperlink tag.
     *
     * @param string            $text    link body. It will NOT be HTML-encoded. Therefore you can pass in HTML code
     *                                   such as an image tag. If this is coming from end users, you should consider {@see \rock\helpers\Html::encode()}
     *                                   it to prevent XSS attacks.
     * @param array|string|null $url     the URL for the hyperlink tag. This parameter will be
     *                                   processed by {@see \rock\url\Url::getAbsoluteUrl()}
     *                                   and will be used for the "href" attribute of the tag. If this parameter is null, the "href" attribute
     *                                   will not be generated.
     * @param array             $options the tag options in terms of name-value pairs. These will be rendered as
     *                                   the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()}.
     *                                   If a value is null, the corresponding attribute will not be rendered.
     *                                   See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated hyperlink
     */
    public static function a($text, $url = null, $options = [])
    {
        if ($url !== null) {
            $urlBuilder = Url::set($url);
            $options['href'] = $urlBuilder->getAbsoluteUrl();
        }

        return static::tag('a', $text, $options);
    }

    /**
     * Generates a mailto hyperlink.
     *
     * @param string $text    link body. It will NOT be HTML-encoded. Therefore you can pass in HTML code
     *                        such as an image tag. If this is coming from end users, you should consider {@see \rock\helpers\Html::encode()}
     *                        it to prevent XSS attacks.
     * @param string $email   email address. If this is null, the first parameter (link body) will be treated
     *                        as the email address and used.
     * @param array  $options the tag options in terms of name-value pairs. These will be rendered as
     *                        the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()} .
     *                        If a value is null, the corresponding attribute will not be rendered.
     *                        See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated mailto link
     */
    public static function mailto($text, $email = null, $options = [])
    {
        $options['href'] = 'mailto:' . ($email === null ? $text : $email);

        return static::tag('a', $text, $options);
    }

    /**
     * Generates an image tag.
     *
     * @param array|string $src     the image URL. This parameter will be
     *                              processed by {@see \rock\url\Url::getAbsoluteUrl()} .
     * @param array        $options the tag options in terms of name-value pairs. These will be rendered as
     *                              the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()} .
     *                              If a value is null, the corresponding attribute will not be rendered.
     *                              See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated image tag
     */
    public static function img($src, $options = [])
    {
        $options['src'] = $src;
        if (!static::isBase64($src)) {
            $urlBuilder = Url::set($src);
            $options['src'] = $urlBuilder->getAbsoluteUrl();
        }
        if (!isset($options['alt'])) {
            $options['alt'] = '';
        }

        return static::tag('img', '', $options);
    }

    /**
     * Generates a label tag.
     *
     * @param string $content label text. It will NOT be HTML-encoded. Therefore you can pass in HTML code
     *                        such as an image tag. If this is is coming from end users, you should {@see \rock\helpers\Html::encode()}
     *                        it to prevent XSS attacks.
     * @param string $for     the ID of the HTML element that this label is associated with.
     *                        If this is null, the "for" attribute will not be generated.
     * @param array  $options the tag options in terms of name-value pairs. These will be rendered as
     *                        the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()} .
     *                        If a value is null, the corresponding attribute will not be rendered.
     *                        See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated label tag
     */
    public static function label($content, $for = null, $options = [])
    {
        $options['for'] = $for;

        return static::tag('label', $content, $options);
    }

    /**
     * Generates a button tag.
     *
     * @param string $content the content enclosed within the button tag. It will NOT be HTML-encoded.
     *                        Therefore you can pass in HTML code such as an image tag. If this is is coming from end users,
     *                        you should consider {@see \rock\helpers\Html::encode()} it to prevent XSS attacks.
     * @param array  $options the tag options in terms of name-value pairs. These will be rendered as
     *                        the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()} .
     *                        If a value is null, the corresponding attribute will not be rendered.
     *                        See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated button tag
     */
    public static function button($content = 'Button', $options = [])
    {
        if (!isset($options['type'])) {
            $options['type'] = 'button';
        }
        return static::tag('button', $content, $options);
    }

    /**
     * Generates a submit button tag.
     *
     * @param string $content the content enclosed within the button tag. It will NOT be HTML-encoded.
     *                        Therefore you can pass in HTML code such as an image tag. If this is is coming from end users,
     *                        you should consider {@see \rock\helpers\Html::encode()} it to prevent XSS attacks.
     * @param array  $options the tag options in terms of name-value pairs. These will be rendered as
     *                        the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()} .
     *                        If a value is null, the corresponding attribute will not be rendered.
     *                        See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated submit button tag
     */
    public static function submitButton($content = 'Submit', $options = [])
    {
        $options['type'] = 'submit';

        return static::button($content, $options);
    }

    /**
     * Generates a reset button tag.
     *
     * @param string $content the content enclosed within the button tag. It will NOT be HTML-encoded.
     *                        Therefore you can pass in HTML code such as an image tag. If this is is coming from end users,
     *                        you should consider {@see \rock\helpers\Html::encode()} it to prevent XSS attacks.
     * @param array  $options the tag options in terms of name-value pairs. These will be rendered as
     *                        the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()} .
     *                        If a value is null, the corresponding attribute will not be rendered.
     *                        See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated reset button tag
     */
    public static function resetButton($content = 'Reset', $options = [])
    {
        $options['type'] = 'reset';

        return static::button($content, $options);
    }

    /**
     * Generates an input type of the given type.
     *
     * @param string $type    the type attribute.
     * @param string $name    the name attribute. If it is null, the name attribute will not be generated.
     * @param string $value   the value attribute. If it is null, the value attribute will not be generated.
     * @param array  $options the tag options in terms of name-value pairs. These will be rendered as
     *                        the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()} .
     *                        If a value is null, the corresponding attribute will not be rendered.
     *                        See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated input tag
     */
    public static function input($type, $name = null, $value = null, $options = [])
    {
        $options['type'] = $type;
        $options['name'] = $name;
        $options['value'] = $value === null ? null : (string)$value;

        return static::tag('input', '', $options);
    }

    /**
     * Generates an input button.
     *
     * @param string $label   the value attribute. If it is null, the value attribute will not be generated.
     * @param array  $options the tag options in terms of name-value pairs. These will be rendered as
     *                        the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()} .
     *                        If a value is null, the corresponding attribute will not be rendered.
     *                        See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated button tag
     */
    public static function buttonInput($label = 'Button', $options = [])
    {
        $options['type'] = 'button';
        $options['value'] = $label;

        return static::tag('input', '', $options);
    }

    /**
     * Generates a submit input button.
     *
     * @param string $label   the value attribute. If it is null, the value attribute will not be generated.
     * @param array  $options the tag options in terms of name-value pairs. These will be rendered as
     *                        the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()} .
     *                        If a value is null, the corresponding attribute will not be rendered.
     *                        See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated button tag
     */
    public static function submitInput($label = 'Submit', $options = [])
    {
        $options['type'] = 'submit';
        $options['value'] = $label;

        return static::tag('input', '', $options);
    }

    /**
     * Generates a reset input button.
     *
     * @param string $label   the value attribute. If it is null, the value attribute will not be generated.
     * @param array  $options the attributes of the button tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()}.
     *                        Attributes whose value is null will be ignored and not put in the tag returned.
     *                        See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated button tag
     */
    public static function resetInput($label = 'Reset', $options = [])
    {
        $options['type'] = 'reset';
        $options['value'] = $label;

        return static::tag('input', '', $options);
    }

    /**
     * Generates a text input field.
     *
     * @param string $name    the name attribute.
     * @param string $value   the value attribute. If it is null, the value attribute will not be generated.
     * @param array  $options the tag options in terms of name-value pairs. These will be rendered as
     *                        the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()} .
     *                        If a value is null, the corresponding attribute will not be rendered.
     *                        See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated button tag
     */
    public static function textInput($name, $value = null, $options = [])
    {
        return static::input('text', $name, $value, $options);
    }

    /**
     * Generates a hidden input field.
     *
     * @param string $name    the name attribute.
     * @param string $value   the value attribute. If it is null, the value attribute will not be generated.
     * @param array  $options the tag options in terms of name-value pairs. These will be rendered as
     *                        the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()} .
     *                        If a value is null, the corresponding attribute will not be rendered.
     *                        See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated button tag
     */
    public static function hiddenInput($name, $value = null, $options = [])
    {
        return static::input('hidden', $name, $value, $options);
    }

    /**
     * Generates a password input field.
     *
     * @param string $name    the name attribute.
     * @param string $value   the value attribute. If it is null, the value attribute will not be generated.
     * @param array  $options the tag options in terms of name-value pairs. These will be rendered as
     *                        the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()} .
     *                        If a value is null, the corresponding attribute will not be rendered.
     *                        See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated button tag
     */
    public static function passwordInput($name, $value = null, $options = [])
    {
        return static::input('password', $name, $value, $options);
    }

    /**
     * Generates a file input field.
     *
     * To use a file input field, you should set the enclosing form's "enctype" attribute to
     * be "multipart/form-data". After the form is submitted, the uploaded file information
     * can be obtained via `$_FILES[$name]` (see PHP documentation).
     *
     * @param string $name    the name attribute.
     * @param string $value   the value attribute. If it is null, the value attribute will not be generated.
     * @param array  $options the tag options in terms of name-value pairs. These will be rendered as
     *                        the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()}.
     *                        If a value is null, the corresponding attribute will not be rendered.
     *                        See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated button tag
     */
    public static function fileInput($name, $value = null, $options = [])
    {
        return static::input('file', $name, $value, $options);
    }

    /**
     * Generates a text area input.
     *
     * @param string $name    the input name
     * @param string $value   the input value. Note that it will be encoded using {@see \rock\helpers\Html::encode()}.
     * @param array  $options the tag options in terms of name-value pairs. These will be rendered as
     *                        the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()} .
     *                        If a value is null, the corresponding attribute will not be rendered.
     *                        See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated text area tag
     */
    public static function textarea($name, $value = '', $options = [])
    {
        $options['name'] = $name;

        return static::tag('textarea', static::encode($value), $options);
    }

    /**
     * Generates a radio button input.
     *
     * @param string  $name    the name attribute.
     * @param boolean $checked whether the radio button should be checked.
     * @param array   $options the tag options in terms of name-value pairs. The following options are specially handled:
     *
     * - uncheck: string, the value associated with the uncheck state of the radio button. When this attribute
     *   is present, a hidden input will be generated so that if the radio button is not checked and is submitted,
     *   the value of this attribute will still be submitted to the server via the hidden input.
     * - label: string, a label displayed next to the radio button.  It will NOT be HTML-encoded. Therefore you can pass
     *   in HTML code such as an image tag. If this is is coming from end users, you should {@see \rock\helpers\Html::encode()} it to prevent XSS attacks.
     *   When this option is specified, the radio button will be enclosed by a label tag.
     * - labelOptions: array, the HTML attributes for the label tag. Do not set this option unless you set the "label" option.
     *
     * The rest of the options will be rendered as the attributes of the resulting radio button tag. The values will
     * be HTML-encoded
     * using {@see \rock\helpers\Html::encode()}. If a value is null, the corresponding attribute will not be rendered.
     * See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return string the generated radio button tag
     */
    public static function radio($name, $checked = false, $options = [])
    {
        $options['checked'] = (bool) $checked;
        $value = array_key_exists('value', $options) ? $options['value'] : '1';
        if (isset($options['uncheck'])) {
            // add a hidden field so that if the radio button is not selected, it still submits a value
            $hidden = static::hiddenInput($name, $options['uncheck']);
            unset($options['uncheck']);
        } else {
            $hidden = '';
        }
        if (isset($options['label'])) {
            $label = $options['label'];
            $labelOptions = isset($options['labelOptions']) ? $options['labelOptions'] : [];
            unset($options['label'], $options['labelOptions']);
            $content = static::label(static::input('radio', $name, $value, $options) . ' ' . $label, null, $labelOptions);
            return $hidden . $content;
        } else {
            return $hidden . static::input('radio', $name, $value, $options);
        }
    }

    /**
     * Generates a checkbox input.
     *
     * @param string  $name    the name attribute.
     * @param boolean $checked whether the checkbox should be checked.
     * @param array   $options the tag options in terms of name-value pairs. The following options are specially handled:
     *
     * - uncheck: string, the value associated with the uncheck state of the checkbox. When this attribute
     *   is present, a hidden input will be generated so that if the checkbox is not checked and is submitted,
     *   the value of this attribute will still be submitted to the server via the hidden input.
     * - label: string, a label displayed next to the checkbox.  It will NOT be HTML-encoded. Therefore you can pass
     *   in HTML code such as an image tag. If this is is coming from end users, you should {@see \rock\helpers\Html::encode()} it to prevent XSS attacks.
     *   When this option is specified, the checkbox will be enclosed by a label tag.
     * - labelOptions: array, the HTML attributes for the label tag. Do not set this option unless you set the "label" option.
     *
     * The rest of the options will be rendered as the attributes of the resulting checkbox tag. The values will
     * be HTML-encoded using {@see \rock\helpers\Html::encode()} . If a value is null, the corresponding attribute will not be rendered.
     * See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return string the generated checkbox tag
     */
    public static function checkbox($name, $checked = false, $options = [])
    {
        $options['checked'] = (boolean)$checked;
        $value = array_key_exists('value', $options) ? $options['value'] : '1';
        if (isset($options['uncheck'])) {
            // add a hidden field so that if the checkbox is not selected, it still submits a value
            $hidden = static::hiddenInput($name, $options['uncheck']);
            unset($options['uncheck']);
        } else {
            $hidden = '';
        }
        if (isset($options['label'])) {
            $label = $options['label'];
            $labelOptions = isset($options['labelOptions']) ? $options['labelOptions'] : [];
            unset($options['label'], $options['labelOptions']);
            $content = static::label(static::input('checkbox', $name, $value, $options) . ' ' . $label, null, $labelOptions);
            return $hidden . $content;
        } else {
            return $hidden . static::input('checkbox', $name, $value, $options);
        }
    }

    /**
     * Generates a drop-down list.
     *
     * @param string $name      the input name
     * @param string $selection the selected value
     * @param array  $items     the option data items. The array keys are option values, and the array values
     *                          are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
     *                          For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
     *                          If you have a list of data models, you may convert them into the format described above using {@see \rock\helpers\ArrayHelper::map()}.
     *
     * Note, the values and labels will be automatically HTML-encoded by this method, and the blank spaces in
     * the labels will also be HTML-encoded.
     * @param array  $options   the tag options in terms of name-value pairs. The following options are specially handled:
     *
     * - prompt: string, a prompt text to be displayed as the first option;
     * - options: array, the attributes for the select option tags. The array keys must be valid option values,
     *   and the array values are the extra attributes for the corresponding option tags. For example,
     *
     *   ```php
     *   [
     *       'value1' => ['disabled' => true],
     *       'value2' => ['label' => 'value 2'],
     *   ];
     *   ```
     *
     * - groups: array, the attributes for the optgroup tags. The structure of this is similar to that of 'options',
     *   except that the array keys represent the optgroup labels specified in $items.
     * - encodeSpaces: bool, whether to encode spaces in option prompt and option value with `&nbsp;` character.
     *   Defaults to `false`.
     *
     * The rest of the options will be rendered as the attributes of the resulting tag. The values will
     * be HTML-encoded using {@see \rock\helpers\Html::encode()}. If a value is null, the corresponding attribute will not be rendered.
     * See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return string the generated drop-down list tag
     */
    public static function dropDownList($name, $selection = null, $items = [], $options = [])
    {
        if (!empty($options['multiple'])) {
            return static::listBox($name, $selection, $items, $options);
        }
        $options['name'] = $name;
        unset($options['unselect']);
        $selectOptions = static::renderSelectOptions($selection, $items, $options);

        return static::tag('select', "\n" . $selectOptions . "\n", $options);
    }

    /**
     * Generates a list box.
     *
     * @param string       $name      the input name
     * @param string|array $selection the selected value(s)
     * @param array        $items     the option data items. The array keys are option values, and the array values
     *                                are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
     *                                For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
     *                                If you have a list of data models, you may convert them into the format described above using {@see \rock\helpers\ArrayHelper::map()}.
     *
     * Note, the values and labels will be automatically HTML-encoded by this method, and the blank spaces in
     * the labels will also be HTML-encoded.
     * @param array        $options   the tag options in terms of name-value pairs. The following options are specially handled:
     *
     * - prompt: string, a prompt text to be displayed as the first option;
     * - options: array, the attributes for the select option tags. The array keys must be valid option values,
     *   and the array values are the extra attributes for the corresponding option tags. For example,
     *
     *   ```php
     *   [
     *       'value1' => ['disabled' => true],
     *       'value2' => ['label' => 'value 2'],
     *   ];
     *   ```
     *
     * - groups: array, the attributes for the optgroup tags. The structure of this is similar to that of 'options',
     *   except that the array keys represent the optgroup labels specified in $items.
     * - unselect: string, the value that will be submitted when no option is selected.
     *   When this attribute is set, a hidden field will be generated so that if no option is selected in multiple
     *   mode, we can still obtain the posted unselect value.
     * - encodeSpaces: bool, whether to encode spaces in option prompt and option value with `&nbsp;` character.
     *   Defaults to `false`.
     *
     * The rest of the options will be rendered as the attributes of the resulting tag. The values will
     * be HTML-encoded using {@see \rock\helpers\Html::encode()}. If a value is null, the corresponding attribute will not be rendered.
     * See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return string the generated list box tag
     */
    public static function listBox($name, $selection = null, $items = [], $options = [])
    {
        if (!array_key_exists('size', $options)) {
            $options['size'] = 4;
        }
        if (!empty($options['multiple']) && !empty($name) && substr_compare($name, '[]', -2, 2)) {
            $name .= '[]';
        }
        $options['name'] = $name;
        if (isset($options['unselect'])) {
            // add a hidden field so that if the list box has no option being selected, it still submits a value
            if (!empty($name) && substr_compare($name, '[]', -2, 2) === 0) {
                $name = substr($name, 0, -2);
            }
            $hidden = static::hiddenInput($name, $options['unselect']);
            unset($options['unselect']);
        } else {
            $hidden = '';
        }
        $selectOptions = static::renderSelectOptions($selection, $items, $options);

        return $hidden . static::tag('select', "\n" . $selectOptions . "\n", $options);
    }

    /**
     * Generates a list of checkboxes.
     *
     * A checkbox list allows multiple selection, like {@see \rock\helpers\Html::listBox()}.
     * As a result, the corresponding submitted value is an array.
     *
     * @param string       $name      the name attribute of each checkbox.
     * @param string|array $selection the selected value(s).
     * @param array        $items     the data item used to generate the checkboxes.
     *                                The array values are the labels, while the array keys are the corresponding checkbox values.
     * @param array        $options   options (name => config) for the checkbox list container tag.
     *                                The following options are specially handled:
     *
     * - tag: string, the tag name of the container element.
     * - unselect: string, the value that should be submitted when none of the checkboxes is selected.
     *   By setting this option, a hidden input will be generated.
     * - encode: boolean, whether to HTML-encode the checkbox labels. Defaults to true.
     *   This option is ignored if `item` option is set.
     * - separator: string, the HTML code that separates items.
     * - itemOptions: array, the options for generating the radio button tag using {@see \rock\helpers\Html::checkbox()}.
     * - item: callable, a callback that can be used to customize the generation of the HTML code
     *   corresponding to a single item in $items. The signature of this callback must be:
     *
     *   ```php
     *   function ($index, $label, $name, $checked, $value)
     *   ```
     *
     *   where $index is the zero-based index of the checkbox in the whole list; $label
     *   is the label for the checkbox; and $name, $value and $checked represent the name,
     *   value and the checked status of the checkbox input, respectively.
     *
     * See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return string the generated checkbox list
     */
    public static function checkboxList($name, $selection = null, $items = [], $options = [])
    {
        if (substr($name, -2) !== '[]') {
            $name .= '[]';
        }

        $formatter = isset($options['item']) ? $options['item'] : null;
        $itemOptions = isset($options['itemOptions']) ? $options['itemOptions'] : [];
        $encode = !isset($options['encode']) || $options['encode'];
        $lines = [];
        $index = 0;
        foreach ($items as $value => $label) {
            $checked = $selection !== null &&
                (!is_array($selection) && !strcmp($value, $selection)
                    || is_array($selection) && in_array($value, $selection));
            if ($formatter !== null) {
                $lines[] = call_user_func($formatter, $index, $label, $name, $checked, $value);
            } else {
                $lines[] = static::checkbox($name, $checked, array_merge($itemOptions, [
                    'value' => $value,
                    'label' => $encode ? static::encode($label) : $label,
                ]));
            }
            $index++;
        }

        if (isset($options['unselect'])) {
            // add a hidden field so that if the list box has no option being selected, it still submits a value
            $name2 = substr($name, -2) === '[]' ? substr($name, 0, -2) : $name;
            $hidden = static::hiddenInput($name2, $options['unselect']);
        } else {
            $hidden = '';
        }
        $separator = isset($options['separator']) ? $options['separator'] : "\n";

        $tag = isset($options['tag']) ? $options['tag'] : 'div';
        unset($options['tag'], $options['unselect'], $options['encode'], $options['separator'], $options['item'], $options['itemOptions']);

        return $hidden . static::tag($tag, implode($separator, $lines), $options);
    }

    /**
     * Generates a list of radio buttons.
     *
     * A radio button list is like a checkbox list, except that it only allows single selection.
     *
     * @param string       $name      the name attribute of each radio button.
     * @param string|array $selection the selected value(s).
     * @param array $items the data item used to generate the radio buttons.
     * The array keys are the radio button values, while the array values are the corresponding labels.
     * @param array        $options   options (name => config) for the radio button list. The following options are supported:
     *
     * - unselect: string, the value that should be submitted when none of the radio buttons is selected.
     *   By setting this option, a hidden input will be generated.
     * - encode: boolean, whether to HTML-encode the checkbox labels. Defaults to true.
     *   This option is ignored if `item` option is set.
     * - separator: string, the HTML code that separates items.
     * - itemOptions: array, the options for generating the radio button tag using @see radio().
     * - item: callable, a callback that can be used to customize the generation of the HTML code
     *   corresponding to a single item in $items. The signature of this callback must be:
     *
     *   ```php
     *   function ($index, $label, $name, $checked, $value)
     *   ```
     *
     *   where $index is the zero-based index of the radio button in the whole list; `$label`
     *   is the label for the radio button; and $name, `$value` and `$checked` represent the name,
     *   value and the checked status of the radio button input, respectively.
     *
     * See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return string the generated radio button list
     */
    public static function radioList($name, $selection = null, $items = [], $options = [])
    {
        $encode = !isset($options['encode']) || $options['encode'];
        $formatter = isset($options['item']) ? $options['item'] : null;
        $itemOptions = isset($options['itemOptions']) ? $options['itemOptions'] : [];
        $lines = [];
        $index = 0;
        foreach ($items as $value => $label) {
            $checked = $selection !== null &&
                (!is_array($selection) && !strcmp($value, $selection)
                    || is_array($selection) && in_array($value, $selection));
            if ($formatter !== null) {
                $lines[] = call_user_func($formatter, $index, $label, $name, $checked, $value);
            } else {
                $lines[] = static::radio($name, $checked, array_merge($itemOptions, [
                    'value' => $value,
                    'label' => $encode ? static::encode($label) : $label,
                ]));
            }
            $index++;
        }

        $separator = isset($options['separator']) ? $options['separator'] : "\n";
        if (isset($options['unselect'])) {
            // add a hidden field so that if the list box has no option being selected, it still submits a value
            $hidden = static::hiddenInput($name, $options['unselect']);
        } else {
            $hidden = '';
        }

        $tag = isset($options['tag']) ? $options['tag'] : 'div';
        unset($options['tag'], $options['unselect'], $options['encode'], $options['separator'], $options['item'], $options['itemOptions']);

        return $hidden . static::tag($tag, implode($separator, $lines), $options);
    }

    /**
     * Generates an unordered list.
     *
     * @param array|\Traversable $items   the items for generating the list. Each item generates a single list item.
     *                                    Note that items will be automatically HTML encoded if `$options['encode']` is not set or true.
     * @param array              $options options (name => config) for the radio button list. The following options are supported:
     *
     * - encode: boolean, whether to HTML-encode the items. Defaults to true.
     * This option is ignored if the `item` option is specified.
     * - itemOptions: array, the HTML attributes for the `li` tags. This option is ignored if the `item` option is specified.
     * - item: callable, a callback that is used to generate each individual list item.
     * The signature of this callback must be:
     *
     * ```php
     * function ($item, $index)
     * ```
     *
     * where $index is the array key corresponding to `$item` in `$items`. The callback should return
     * the whole list item tag.
     *
     * See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return string the generated unordered list. An empty list tag will be returned if `$items` is empty.
     */
    public static function ul($items, $options = [])
    {
        $tag = isset($options['tag']) ? $options['tag'] : 'ul';
        $encode = !isset($options['encode']) || $options['encode'];
        $formatter = isset($options['item']) ? $options['item'] : null;
        $itemOptions = isset($options['itemOptions']) ? $options['itemOptions'] : [];
        unset($options['tag'], $options['encode'], $options['item'], $options['itemOptions']);

        if (empty($items)) {
            return static::tag($tag, '', $options);
        }

        $results = [];
        foreach ($items as $index => $item) {
            if ($formatter !== null) {
                $results[] = call_user_func($formatter, $item, $index);
            } else {
                $results[] = static::tag('li', $encode ? static::encode($item) : $item, $itemOptions);
            }
        }

        return static::tag($tag, "\n" . implode("\n", $results) . "\n", $options);
    }

    /**
     * Generates an ordered list.
     *
     * @param array|\Traversable $items   the items for generating the list. Each item generates a single list item.
     *                                    Note that items will be automatically HTML encoded if `$options['encode']` is not set or true.
     * @param array              $options options (name => config) for the radio button list. The following options are supported:
     *
     * - encode: boolean, whether to HTML-encode the items. Defaults to true.
     *   This option is ignored if the `item` option is specified.
     * - itemOptions: array, the HTML attributes for the `li` tags. This option is ignored if the `item` option is specified.
     * - item: callable, a callback that is used to generate each individual list item.
     *   The signature of this callback must be:
     *
     *   ```php
     *   function ($item, $index)
     *   ```
     *
     *   where $index is the array key corresponding to `$item` in `$items`. The callback should return
     *   the whole list item tag.
     *
     * See {@see \rock\helpers\Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return string the generated ordered list. An empty string is returned if `$items` is empty.
     */
    public static function ol($items, $options = [])
    {
        $options['tag'] = 'ol';

        return static::ul($items, $options);
    }

    /**
     * Renders the option tags.
     *
     * That can be used by {@see \rock\helpers\Html::dropDownList()} and {@see \rock\helpers\Html::listBox()}.
     *
     * @param string|array $selection  the selected value(s). This can be either a string for single selection
     *                                 or an array for multiple selections.
     * @param array        $items      the option data items. The array keys are option values, and the array values
     *                                 are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
     *                                 For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
     *                                 If you have a list of data models, you may convert them into the format described above using {@see \rock\helpers\ArrayHelper::map()}.
     *
     * Note, the values and labels will be automatically HTML-encoded by this method, and the blank spaces in
     * the labels will also be HTML-encoded.
     * @param array        $tagOptions the $options parameter that
     *                                 is passed to the {@see \rock\helpers\Html::dropDownList()} or {@see \rock\helpers\Html::listBox()} call.
     *                                 This method will take out these elements, if any: "prompt", "options" and "groups". See more details
     *                                 in {@see \rock\helpers\Html::dropDownList()} for the explanation of these elements.
     *
     * @return string the generated list options
     */
    public static function renderSelectOptions($selection, $items, &$tagOptions = [])
    {
        $lines = [];
        $encodeSpaces = static::remove($tagOptions, 'encodeSpaces', false);
        if (isset($tagOptions['prompt'])) {
            $prompt = $encodeSpaces ? str_replace(' ', '&nbsp;', static::encode($tagOptions['prompt'])) : static::encode($tagOptions['prompt']);
            $lines[] = static::tag('option', $prompt, ['value' => '']);
        }

        $options = isset($tagOptions['options']) ? $tagOptions['options'] : [];
        $groups = isset($tagOptions['groups']) ? $tagOptions['groups'] : [];
        unset($tagOptions['prompt'], $tagOptions['options'], $tagOptions['groups']);
        $options['encodeSpaces'] = ArrayHelper::getValue($options, 'encodeSpaces', $encodeSpaces);

        foreach ($items as $key => $value) {
            if (is_array($value)) {
                $groupAttrs = isset($groups[$key]) ? $groups[$key] : [];
                $groupAttrs['label'] = $key;
                $attrs = ['options' => $options, 'groups' => $groups, 'encodeSpaces' => $encodeSpaces];
                $content = static::renderSelectOptions($selection, $value, $attrs);
                $lines[] = static::tag('optgroup', "\n" . $content . "\n", $groupAttrs);
            } else {
                $attrs = isset($options[$key]) ? $options[$key] : [];
                $attrs['value'] = (string) $key;
                $attrs['selected'] = $selection !== null &&
                        (!is_array($selection) && !strcmp($key, $selection)
                        || is_array($selection) && in_array($key, $selection));
                $lines[] = static::tag('option', ($encodeSpaces ? str_replace(' ', '&nbsp;', static::encode($value)) : static::encode($value)), $attrs);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Renders the HTML tag attributes.
     *
     * Attributes whose values are of boolean type will be treated as
     * [boolean attributes](http://www.w3.org/TR/html5/infrastructure.html#boolean-attributes).
     *
     * Attributes whose values are null will not be rendered.
     *
     * The values of attributes will be HTML-encoded using {@see \rock\helpers\Html::encode()}.
     *
     * The "data" attribute is specially handled when it is receiving an array value. In this case,
     * the array will be "expanded" and a list data attributes will be rendered. For example,
     * if `'data' => ['id' => 1, 'name' => 'rock']`, then this will be rendered:
     * `data-id="1" data-name="rock"`.
     * Additionally `'data' => ['params' => ['id' => 1, 'name' => 'rock'], 'status' => 'ok']` will be rendered as:
     * `data-params='{"id":1,"name":"rock"}' data-status="ok"`.
     *
     * @param array $attributes attributes to be rendered.
     *                          The attribute values will be HTML-encoded using {@see \rock\helpers\Html::encode()} .
     * @return string the rendering result. If the attributes are not empty, they will be rendered
     *                          into a string with a leading white space (so that it can be directly appended to the tag name
     *                          in a tag. If there is no attribute, an empty string will be returned.
     */
    public static function renderTagAttributes($attributes)
    {
        unset($attributes['wrapperTpl']);
        if (count($attributes) > 1) {
            $sorted = [];
            foreach (static::$attributeOrder as $name) {
                if (isset($attributes[$name])) {
                    $sorted[$name] = $attributes[$name];
                }
            }
            $attributes = array_merge($sorted, $attributes);
        }

        $html = '';
        foreach ($attributes as $name => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html .= " $name";
                }
            } elseif (is_array($value) && $name === 'data') {
                foreach ($value as $n => $v) {
                    if (is_array($v)) {
                        $html .= " $name-$n='" . Json::encode($v, JSON_HEX_APOS) . "'";
                    } else {
                        $html .= " $name-$n=\"" . static::encode($v) . '"';
                    }
                }
            } elseif ($value !== null) {
                $html .= " $name=\"" . static::encode($value) . '"';
            }
        }

        return $html;
    }

    /**
     * Adds a CSS class to the specified options.
     *
     * If the CSS class is already in the options, it will not be added again.
     *
     * @param array  $options the options to be modified.
     * @param string $class   the CSS class to be added
     */
    public static function addCssClass(&$options, $class)
    {
        if (isset($options['class'])) {
            $classes = ' ' . $options['class'] . ' ';
            if (strpos($classes, ' ' . $class . ' ') === false) {
                $options['class'] .= ' ' . $class;
            }
        } else {
            $options['class'] = $class;
        }
    }

    /**
     * Removes a CSS class from the specified options.
     *
     * @param array  $options the options to be modified.
     * @param string $class   the CSS class to be removed
     */
    public static function removeCssClass(&$options, $class)
    {
        if (isset($options['class'])) {
            $classes = array_unique(preg_split('/\s+/', $options['class'] . ' ' . $class, -1, PREG_SPLIT_NO_EMPTY));
            if (($index = array_search($class, $classes)) !== false) {
                unset($classes[$index]);
            }
            if (empty($classes)) {
                unset($options['class']);
            } else {
                $options['class'] = implode(' ', $classes);
            }
        }
    }

    /**
     * Adds the specified CSS style to the HTML options.
     *
     * If the options already contain a `style` element, the new style will be merged
     * with the existing one. If a CSS property exists in both the new and the old styles,
     * the old one may be overwritten if `$overwrite` is true.
     *
     * For example,
     *
     * ```php
     * Html::addCssStyle($options, 'width: 100px; height: 200px');
     * ```
     *
     * @param array        $options   the HTML options to be modified.
     * @param string|array $style     the new style string (e.g. `'width: 100px; height: 200px'`) or
     *                                array (e.g. `['width' => '100px', 'height' => '200px']`).
     * @param boolean      $overwrite whether to overwrite existing CSS properties if the new style
     *                                contain them too.
     * @see removeCssStyle()
     * @see cssStyleFromArray()
     * @see cssStyleToArray()
     */
    public static function addCssStyle(&$options, $style, $overwrite = true)
    {
        if (!empty($options['style'])) {
            $oldStyle = static::cssStyleToArray($options['style']);
            $newStyle = is_array($style) ? $style : static::cssStyleToArray($style);
            if (!$overwrite) {
                foreach ($newStyle as $property => $value) {
                    if (isset($oldStyle[$property])) {
                        unset($newStyle[$property]);
                    }
                }
            }
            $style = static::cssStyleFromArray(array_merge($oldStyle, $newStyle));
        }
        $options['style'] = $style;
    }

    /**
     * Removes the specified CSS style from the HTML options.
     *
     * For example,
     *
     * ```php
     * Html::removeCssStyle($options, ['width', 'height']);
     * ```
     *
     * @param array        $options    the HTML options to be modified.
     * @param string|array $properties the CSS properties to be removed. You may use a string
     *                                 if you are removing a single property.
     * @see addCssStyle()
     */
    public static function removeCssStyle(&$options, $properties)
    {
        if (!empty($options['style'])) {
            $style = static::cssStyleToArray($options['style']);
            foreach ((array)$properties as $property) {
                unset($style[$property]);
            }
            $options['style'] = static::cssStyleFromArray($style);
        }
    }

    /**
     * Converts a CSS style array into a string representation.
     *
     * For example,
     *
     * ```php
     * print_r(Html::cssStyleFromArray(['width' => '100px', 'height' => '200px']));
     * // will display: 'width: 100px; height: 200px;'
     * ```
     *
     * @param array $style the CSS style array. The array keys are the CSS property names,
     *                     and the array values are the corresponding CSS property values.
     * @return string the CSS style string. If the CSS style is empty, a null will be returned.
     */
    public static function cssStyleFromArray(array $style)
    {
        $result = '';
        foreach ($style as $name => $value) {
            $result .= "$name: $value; ";
        }

        // return null if empty to avoid rendering the "style" attribute
        return $result === '' ? null : rtrim($result);
    }

    /**
     * Converts a CSS style string into an array representation.
     *
     * The array keys are the CSS property names, and the array values
     * are the corresponding CSS property values.
     *
     * For example,
     *
     * ```php
     * print_r(Html::cssStyleToArray('width: 100px; height: 200px;'));
     * // will display: ['width' => '100px', 'height' => '200px']
     * ```
     *
     * @param string $style the CSS style string
     * @return array the array representation of the CSS style
     */
    public static function cssStyleToArray($style)
    {
        $result = [];
        foreach (explode(';', $style) as $property) {
            $property = explode(':', $property);
            if (count($property) > 1) {
                $result[trim($property[0])] = trim($property[1]);
            }
        }

        return $result;
    }

    /**
     * Removes an item from an array and returns the value.
     *
     * If the key does not exist in the array, the default value will be returned instead.
     *
     * Usage examples,
     *
     * ```php
     * // $array = ['type' => 'A', 'options' => [1, 2]];
     * // working with array
     * $type = Html::remove($array, 'type');
     * // $array content
     * // $array = ['options' => [1, 2]];
     * ```
     *
     * @param array  $array   the array to extract value from
     * @param string $key     key name of the array element
     * @param mixed  $default the default value to be returned if the specified key does not exist
     * @return mixed|null the value of the element if found, default value otherwise
     */
    public static function remove(&$array, $key, $default = null)
    {
        if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))) {
            $value = $array[$key];
            unset($array[$key]);

            return $value;
        }

        return $default;
    }

    protected static function isBase64($value)
    {
        return mb_substr($value, 0, 5, 'UTF-8') === 'data:';
    }

    /**
     * @return null|CSRF
     * @throws \rock\di\ContainerException
     */
    protected static function getCSRF()
    {
        if (class_exists('\rock\di\Container')) {
            return Container::load('csrf');
        }

        if (class_exists('\rock\csrf\CSRF')) {
            return new CSRF();
        }

        return null;
    }

    /**
     * @return null|Request
     * @throws \rock\di\ContainerException
     */
    protected static function getRequest()
    {
        if (class_exists('\rock\di\Container')) {
            return Container::load('request');
        }

        if (class_exists('\rock\request\Request')) {
            return new Request();
        }

        return null;
    }
} 