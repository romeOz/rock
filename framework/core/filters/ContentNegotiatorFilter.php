<?php
namespace rock\filters;

use rock\request\Request;
use rock\response\Response;
use rock\Rock;

/**
 * ContentNegotiator supports response format negotiation and application language negotiation.
 *
 * When the [[formats|supported formats]] property is specified, ContentNegotiator will support response format
 * negotiation based on the value of the GET parameter [[formatParam]] and the `Accept` HTTP header.
 * If a match is found, the [[Response::format]] property will be set as the chosen format.
 * The [[Response::acceptMimeType]] as well as [[Response::acceptParams]] will also be updated accordingly.
 *
 * When the [[languages|supported languages]] is specified, ContentNegotiator will support application
 * language negotiation based on the value of the GET parameter [[languageParam]] and the `Accept-Language` HTTP header.
 * If a match is found, the [[Rock::$app->language]] property will be set as the chosen language.
 *
 *
 * The following code shows how you can use ContentNegotiator as an action filter in either a controller or a module.
 * In this case, the content negotiation result only applies to the corresponding controller or module, or even
 * specific actions if you configure the `only` or `except` property of the filter.
 *
 * ```php
 *
 * public function behaviors()
 * {
 *     return [
 *         [
 *             'class' => ContentNegotiatorFilter::className(),
 *             'only' => ['actionView', 'actionIndex'],  // in a controller
 *             'formats' => [
 *                 'application/json' => Response::FORMAT_JSON,
 *             ],
 *             'languages' => [
 *                 'en',
 *                 'de',
 *             ],
 *         ],
 *     ];
 * }
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ContentNegotiatorFilter extends ActionFilter
{
    /**
     * @var string the name of the GET parameter that specifies the response format.
     * Note that if the specified format does not exist in [[formats]], a [[ContentNegotiatorFilter]]
     * exception will be thrown.  If the parameter value is empty or if this property is null,
     * the response format will be determined based on the `Accept` HTTP header only.
     * @see formats
     */
    public $formatParam = '_format';
    /**
     * @var string the name of the GET parameter that specifies the [[Rock::$app->language|application language]].
     * Note that if the specified language does not match any of [[languages]], the first language in [[languages]]
     * will be used. If the parameter value is empty or if this property is null,
     * the application language will be determined based on the `Accept-Language` HTTP header only.
     * @see languages
     */
    public $languageParam = '_lang';
    /**
     * @var array list of supported response formats. The keys are MIME types (e.g. `application/json`)
     * while the values are the corresponding formats (e.g. `html`, `json`) which must be supported
     * as declared in [[Response::formatters]].
     *
     * If this property is empty or not set, response format negotiation will be skipped.
     */
    public $formats;
    /**
     * @var array a list of supported languages. The array keys are the supported language variants (e.g. `en-GB`, `en-US`),
     * while the array values are the corresponding language codes (e.g. `en`, `de`) recognized by the application.
     *
     * Array keys are not always required. When an array value does not have a key, the matching of the requested language
     * will be based on a language fallback mechanism. For example, a value of `en` will match `en`, `en_US`, `en-US`, `en-GB`, etc.
     *
     * If this property is empty or not set, language negotiation will be skipped.
     */
    public $languages;
    /**
     * @var Request the current request. If not set, the `request` application component will be used.
     */
    public $request;
    /**
     * @var Response the response to be sent. If not set, the `response` application component will be used.
     */
    public $response;


//    /**
//     * @inheritdoc
//     */
//    public function bootstrap($app)
//    {
//        $this->negotiate();
//    }

    /**
     * {@inheritdoc}
     */
    public function before($action = null)
    {
        if (!$this->validateActions($action)) {

            return parent::before();
        }
        $this->negotiate();
        return parent::before();
    }

    /**
     * Negotiates the response format and application language.
     */
    public function negotiate()
    {
        $request = $this->request ? : Rock::$app->request;
        $response = $this->response ? : Rock::$app->response;
        if (!empty($this->formats)) {
            $this->negotiateContentType($request, $response);
        }
        if (!empty($this->languages)) {
            Rock::$app->language = $this->negotiateLanguage($request);
        }
    }

    /**
     * Negotiates the response format.
     * @param Request $request
     * @param Response $response
     * @throws ContentNegotiatorFilterException if none of the requested content types is accepted.
     */
    protected function negotiateContentType($request, $response)
    {
        if (!empty($this->formatParam) && ($format = Request::get($this->formatParam)) !== null) {
            if (in_array($format, $this->formats)) {
                $response::$format = $format;
                $response->acceptMimeType = null;
                $response->acceptParams = [];
                return;
            } else {
                throw new ContentNegotiatorFilterException(ContentNegotiatorFilterException::CRITICAL, 'The requested response format is not supported: ' . $format);
            }
        }

        $types = $request->getAcceptableContentTypes();
        if (empty($types)) {
            $types['*/*'] = [];
        }
        foreach ($types as $type => $params) {
            if (isset($this->formats[$type])) {
                $response::$format = $this->formats[$type];
                $response->acceptMimeType = $type;
                $response->acceptParams = $params;
                return;
            }
        }

        if (isset($types['*/*'])) {
            // return the first format
            foreach ($this->formats as $type => $format) {
                $response::$format = $this->formats[$type];
                $response->acceptMimeType = $type;
                $response->acceptParams = [];
                return;
            }
        }

        throw new ContentNegotiatorFilterException(ContentNegotiatorFilterException::CRITICAL, 'None of your requested content types is supported.');
    }

    /**
     * Negotiates the application language.
     * @param Request $request
     * @return string the chosen language
     */
    protected function negotiateLanguage($request)
    {
        if (!empty($this->languageParam) && ($language = Request::get($this->languageParam)) !== null) {
            if (isset($this->languages[$language])) {
                return $this->languages[$language];
            }
            foreach ($this->languages as $key => $supported) {
                if (is_integer($key) && $this->isLanguageSupported($language, $supported)) {
                    return $supported;
                }
            }
            return Request::getPreferredLanguage(Rock::$app->allowLanguages);//reset($this->languages);
        }

        foreach ($request->getAcceptableLanguages() as $language => $params) {
            if (isset($this->languages[$language])) {
                return $this->languages[$language];
            }
            foreach ($this->languages as $key => $supported) {
                if (is_integer($key) && $this->isLanguageSupported($language, $supported)) {
                    return $supported;
                }
            }
        }

        return Request::getPreferredLanguage(Rock::$app->allowLanguages);//reset($this->languages);
    }

    /**
     * Returns a value indicating whether the requested language matches the supported language.
     * @param string $requested the requested language code
     * @param string $supported the supported language code
     * @return boolean whether the requested language is supported
     */
    protected function isLanguageSupported($requested, $supported)
    {
        $supported = str_replace('_', '-', strtolower($supported));
        $requested = str_replace('_', '-', strtolower($requested));
        return strpos($requested . '-', $supported . '-') === 0;
    }
}
