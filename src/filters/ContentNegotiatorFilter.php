<?php
namespace rock\filters;

use rock\helpers\Instance;
use rock\request\Request;
use rock\response\Response;
use rock\Rock;

/**
 * ContentNegotiator supports response format negotiation and application language negotiation.
 *
 * When the {@see \rock\filters\ContentNegotiatorFilter::$formats} property is specified, ContentNegotiator will support response format
 * negotiation based on the value of the GET parameter {@see rock\filters\ContentNegotiatorFilter::$formatParam} and the `Accept` HTTP header.
 * If a match is found, the {@see \rock\response\Response::$format} property will be set as the chosen format.
 * The {@see \rock\response\Response::$acceptMimeType} as well as  {@see \rock\response\Response::$acceptParams} will also be updated accordingly.
 *
 * When the {@see \rock\filters\ContentNegotiatorFilter::$languages} is specified, ContentNegotiator will support application
 * language negotiation based on the value of the GET parameter {@see \rock\filters\ContentNegotiatorFilter::$languageParam}
 * and the `Accept-Language` HTTP header.
 * If a match is found, the {@see \rock\Rock::$language} property will be set as the chosen language.
 *
 *
 * The following code shows how you can use ContentNegotiator as an action filter in either a controller or a module.
 * In this case, the content negotiation result only applies to the corresponding controller or module, or even
 * specific actions if you configure the `only` or `except` property of the filter.
 *
 * ```php
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
 */
class ContentNegotiatorFilter extends ActionFilter
{
    /**
     * @var string the name of the GET parameter that specifies the response format.
     * Note that if the specified format does not exist in {@see \rock\response\Response::$format}s,
     * a {@see \rock\filters\ContentNegotiatorFilter}
     * exception will be thrown.  If the parameter value is empty or if this property is null,
     * the response format will be determined based on the `Accept` HTTP header only.
     * @see formats
     */
    public $formatParam = '_format';
    /**
     * @var string the name of the GET parameter that specifies
     * the {@see \rock\Rock::$language}. Note that if the specified language does not match
     * any of {@see \rock\filters\ContentNegotiatorFilter::$languages},
     * the first language in {@see \rock\filters\ContentNegotiatorFilter::$languages}
     * will be used. If the parameter value is empty or if this property is null,
     * the application language will be determined based on the `Accept-Language` HTTP header only.
     * @see languages
     */
    public $languageParam = '_lang';
    /**
     * @var array list of supported response formats. The keys are MIME types (e.g. `application/json`)
     * while the values are the corresponding formats (e.g. `html`, `json`) which must be supported
     * as declared in {@see \rock\response\Response::$format}ters.
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
     * @var Request|string|array the current request. If not set, the `request` application component will be used.
     */
    public $request = 'request';
    /**
     * @var Response|string|array the response to be sent. If not set, the `response` application component will be used.
     */
    public $response = 'response';

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
    public function beforeAction($action)
    {
        $this->negotiate();
        return true;
    }

    /**
     * Negotiates the response format and application language.
     */
    public function negotiate()
    {
        $this->request = Instance::ensure($this->request, '\rock\request\Request');
        $this->response = Instance::ensure($this->response, '\rock\response\Response');

        if (!empty($this->formats)) {
            $this->negotiateContentType($this->request, $this->response);
        }
        if (!empty($this->languages)) {
            Rock::$app->language = $this->negotiateLanguage($this->request);
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
                $response->format = $format;
                $response->acceptMimeType = null;
                $response->acceptParams = [];
                return;
            } else {
                throw new ContentNegotiatorFilterException('The requested response format is not supported: ' . $format);
            }
        }

        $types = $request->getAcceptableContentTypes();
        if (empty($types)) {
            $types['*/*'] = [];
        }
        foreach ($types as $type => $params) {
            if (isset($this->formats[$type])) {
                $response->format = $this->formats[$type];
                $response->acceptMimeType = $type;
                $response->acceptParams = $params;
                return;
            }
        }

        if (isset($types['*/*'])) {
            // return the first format
            foreach ($this->formats as $type => $format) {
                $response->format = $this->formats[$type];
                $response->acceptMimeType = $type;
                $response->acceptParams = [];
                return;
            }
        }

        throw new ContentNegotiatorFilterException('None of your requested content types is supported.');
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
            return $request->getPreferredLanguage();//reset($this->languages);
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

        return $request->getPreferredLanguage();//reset($this->languages);
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