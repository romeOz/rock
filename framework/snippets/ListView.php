<?php
namespace rock\snippets;

use rock\base\Snippet;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\helpers\Json;

/**
 * Snippet "ListView"
 *
 * Examples:
 *
 * ```
 * [[ListView
 *      ?array=`[{"name" : "Tom", "email" : "tom@site.com"}, {"name" : "Chuck", "email" : "chuck@site.com"}]`
 *      ?tpl=`@INLINE<h1>[[+name]]</h1>[[+email]]`
 *      ?wrapperTpl=`@INLINE<p>[[+output]]</p>`
 * ]]
 *
 * [[ListView
 *      ?call=`\foo\FooController.getAll`
 *      ?tpl=`/path/to/chunk_item`
 *      ?wrapperTpl=`@INLINE<p>[[+output]][[++pagination]]</p>`
 *      ?pagination=`{
 *              "call" : "\\foo\\FooController.getPagination",
 *              "toPlaceholder" : "pagination"
 *      }`
 * ]]
 * ```
 *
 * As PHP engine
 *
 * ```php
 * $template = new \rock\Template;
 *
 * $items = [
 *  [
 *      'name' => 'Tom',
 *      'email' => 'tom@site.com',
 *      'about' => '<b>biography</b>'
 *  ],
 *  [
 *      'name' => 'Chuck',
 *      'email' => 'chuck@site.com'
 *  ]
 * ];
 *
 * $params = [
 *      'array' => $items
 *      'pagination' => [
 *          'array' => \rock\helpers\Pagination::get(count($items), (int)$_GET['page'])
 *      ]
 * ];
 * $template->getSnippet('ListView', $params);
 * ```
 *
 * With ActiveDataProvider:
 *
 * ```php
 * $provider = new \rock\db\ActiveDataProvider(
 *  [
 *      'query' => Post::find()->asArray()->all(),
 *      'pagination' => ['limit' => 10, 'sort' => SORT_DESC, 'pageCurrent' => (int)$_GET['num']]
 *  ]
 * );
 *
 *  $params = [
 *      'array' => $provider->get()
 *      'pagination' => [
 *          'array' => $provider->getPagination(),
 *          'pageVar' => 'num'
 *      ]
 *      '
 * ];
 * $template->getSnippet('ListView', $params);
 * ```
 *
 */
class ListView extends Snippet
{
    /**
     * The data as an array
     *
     * @var array
     */
    public $array = [];

    /**
     * The data as an call
     * May be a callable, snippet, and instance
     *
     * ```
     * [[ListView?call=`\foo\FooController.getAll`]]
     * [[ListView?call=`context.getAll`]] - self context
     * ```
     *
     * ```php
     * $params = [
     *  'call' => ['\foo\FooController', 'getAll']
     * ];
     * (new \rock\Template)->getSnippet('ListView', $params);
     * ```
     *
     * @var mixed
     */
    public $call;

    /**
     * Adding external placeholders in `tpl` and `wrapperTpl`.
     *
     * @var array
     */
    public $addPlaceholders = [];

    /**
     * Params pagination
     *          => array            - data of pagination as an array
     *          => call             - data of pagination as an call
     *          => toPlaceholder    - the name of global placeholder to adding the pagination
     *          => pageLimit        - count buttons of pagination
     *          => pageVar          - name url-argument of pagination ("page" by default)
     *          => pageArgs         - url-arguments of pagination
     *          => pageAnchor       - url-anchor of pagination
     *          => wrapperTpl       - wrapper template of pagination
     *          => pageNumTpl       - template for buttons
     *          => pageActiveTpl    - template for active button
     *          => pageFirstTpl     - template for button "first"
     *          => pageLastTpl      - template for button  "last"
     *
     * @var array
     */
    public $pagination = [];

    /**
     * Prepare item
     * @var array
     *
     * ```php
     * ['call' => '\foo\Snippet', 'params' => [...]]
     * ['call' => function{}()]
     * ['call' => [Foo::className(), 'staticMethod']]
     * ['call' => [new Foo(), 'method']]
     * ```
     */
    public $prepare;

    /**
     * name of template
     *
     * @var string
     */
    public $tpl;

    /**
     * name of wrapper template
     *
     * @var string
     */
    public $wrapperTpl;

    /**
     * result to global placeholder (name of global placeholder)
     *
     * @var string
     */
    public $toPlaceholder;

    /**
     * text of error
     *
     * @var string
     */
    public $errorText = '';

    /**
     * @var int|bool
     */
    public $autoEscape = false;
        


    public function get()
    {
        if (empty($this->array) && empty($this->call)) {
            return $this->getError();
        }
        $this->calculateArray();
        $this->calculatePagination();
        if (empty($this->array) || !is_array($this->array)) {
            return null;
        }
        return $this->renderTpl();
    }

    /**
     * Get text of error
     *
     * @return string
     */
    protected function getError()
    {
        return $this->errorText;
    }

    protected function calculateArray()
    {
        $this->array = Helper::getValue($this->array);
        if (!empty($this->call)) {
            $this->array = $this->callFunction($this->call);
        }
        if (!empty($this->array) && is_scalar($this->array)) {
            $this->array =[$this->array];
        }
        if (!empty($this->array) && !is_int(key($this->array))) {
            $this->array = [$this->array];
        }
    }

    /**
     * Adding pagination
     *
     * @return void
     */
    protected function calculatePagination()
    {
        if (empty($this->pagination['array']) && empty($this->pagination['call'])) {
            return;
        }
        if (empty($this->pagination['pageSort'])) {
            $this->pagination['pageSort'] = SORT_DESC;
        }
        if (empty($this->pagination['pageLimit'])) {
            $this->pagination['pageLimit'] = \rock\helpers\Pagination::PAGE_LIMIT;
        }

        if (isset($this->pagination['call'])) {
            $this->pagination['array'] = $this->callFunction($this->pagination['call']);
        }

        $keys = [
            'array',
            'pageVar',
            'pageArgs',
            'wrapperTpl',
            'pageNumTpl',
            'pageActiveTpl',
            'pageFirstTpl',
            'pageLastTpl',
            'pageArgs',
            'pageAnchor'
        ];
        $pagination = $this->template->getSnippet('Pagination', ArrayHelper::intersectByKeys($this->pagination, $keys));
        if (!empty($this->pagination['toPlaceholder'])) {
            $this->template->addPlaceholder($this->pagination['toPlaceholder'], $pagination, true);
            $this->template->cachePlaceholders[$this->pagination['toPlaceholder']] = $pagination;
            return;
        }
        $this->template->addPlaceholder('pagination', $pagination);
    }


    /**
     * Parsing template
     *
     * @return string|null
     */
    protected function renderTpl()
    {
        if (empty($this->tpl)) {
            return Json::encode($this->array);
        }
        $i = 1;
        $result = '';
        $countItems = count($this->array);
        //Adding placeholders
        $addPlaceholders = $this->template->calculateAddPlaceholders($this->addPlaceholders);
        $addPlaceholders['countItems'] = $countItems;
        $placeholders = [];

        foreach ($this->array as $placeholders) {
            if (is_array($placeholders)) {
                $placeholders['currentItem'] = $i;
                $this->prepareItem($placeholders);
                $result .= $this->template->replaceByPrefix(
                    $this->tpl,
                    array_merge($placeholders, $addPlaceholders)
                );
                ++$i;
                continue;
            }
            $result .= $this->template->replaceByPrefix(
                $this->tpl,
                array_merge($addPlaceholders, ['output' => $placeholders, 'currentItem' => $i]));

            ++$i;
        }

        // Deleting placeholders
        if (is_array($placeholders)) {
            $this->template->removeMultiPlaceholders(array_keys($placeholders));
        }
        // Inserting content into wrapper template (optional)
        if (!empty($this->wrapperTpl)) {
            $result = $this->renderWrapperTpl($result, $addPlaceholders);
        }
        // Concat pagination
        $result .= $this->template->getPlaceholder('pagination', false);
        // Deleting placeholders
        $this->template->removePlaceholder('pagination');
        $this->template->removeMultiPlaceholders(array_keys($addPlaceholders));
        // To placeholder
        if (!empty($this->toPlaceholder)) {
            $this->template->addPlaceholder($this->toPlaceholder, $result, true);
            $this->template->cachePlaceholders[$this->toPlaceholder] = $result;
            return null;
        }

        return $result;
    }

    /**
     * @param array $placeholders
     *
     * ```php
     * ['call' => '\foo\FooSnippet', 'params' => [...]]
     * ['call' => function{}()]
     * ['call' => [Foo::className(), 'staticMethod']]
     * ['call' => [new Foo(), 'method']]
     * ```
     */
    protected function prepareItem(array &$placeholders)
    {
        if (empty($this->prepare['call'])) {
            return;
        }
        $this->prepare['params'] = Helper::getValue($this->prepare['params'], []);
        $this->prepare['params']['placeholders'] = $placeholders;
        $this->prepare['params']['autoEscape'] = false;
        $placeholders = $this->callFunction($this->prepare['call'], $this->prepare['params']);
    }

    /**
     * Inserting content into wrapper template
     *
     * @param string $value - content
     * @param array  $placeholders
     * @return string
     */
    protected function renderWrapperTpl($value, array $placeholders)
    {
        $placeholders['output'] = $value;
        $value = $this->template->replaceByPrefix($this->wrapperTpl, $placeholders);
        $this->template->removePlaceholder('output');

        return $value;
    }
}