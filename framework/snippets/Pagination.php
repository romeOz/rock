<?php
namespace rock\snippets;

use rock\base\Snippet;
use rock\helpers\Helper;
use rock\helpers\String;
use rock\Rock;

/**
 * Snippet "Pagination"
 *
 * Examples:
 *
 * ```php
 * $template = new \rock\Template;
 * $countItems = 10;
 * $params = [
 *      'array' => \rock\helpers\Pagination::get($countItems, (int)$_GET['page'], SORT_DESC)
 * ];
 * $template->getSnippet('Pagination', $params);
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
 *      'array' => $provider->getPagination(),
 *      'pageVar' => 'num'
 * ];
 * $template->getSnippet('\rock\snippet\Pagination', $params);
 * ```
 */
class Pagination extends Snippet
{
    /**
     * @var array
     */
    public $array;

    /**
     * May be a callable, snippet, and instance
     *
     * ```
     * [[Pagination?call=`\foo\FooController.getPagination`]]
     * [[Pagination?call=`context.getPagination`]] - self context
     * ```
     *
     * ```php
     * $params = [
     *  'call' => ['\foo\FooController', 'getPagination']
     * ];
     * (new \rock\Template)->getSnippet('Pagination', $params);
     * ```
     *
     * @var string|array
     */
    public $call;

    public $pageVar;

    /**
     * tpl active
     *
     * @var string
     */
    public $pageActiveTpl;

    public $pageNumTpl;

    public $pageFirstName;

    public $pageFirstTpl;

    public $pageLastName;

    public $pageLastTpl;

    public $wrapperTpl;

    /**
     * url-arguments
     *
     * @var
     */
    public $pageArgs;
    public $pageAnchor;

    public $autoEscape = false;


    public function get()
    {
        if (empty($this->array) && empty($this->call)) {
            return null;
        }
        $this->calculateArray();
        if (!isset($this->array['pageCount']) ||
            (int)$this->array['pageCount'] === 1 ||
            empty($this->array['pageDisplay'])
        ) {
            return null;
        }
        $data = $this->array;
        /**
         * if exits args-url
         */
        if (!$this->calculateArgs()) {
            return null;
        }
        /**
         * set name of arg-url by pagination
         */
        $pageVar = !empty($this->pageVar)
            ? $this->pageVar
            : (!empty($data['pageVar'])
                ? $data['pageVar']
                : \rock\helpers\Pagination::PAGE_VAR
            );
        /**
         * Numeration
         */
        $num = $this->calculateNum($data, $pageVar);
        $pageFirstName = $this->calculateFirstPage($data, $pageVar);
        $pageLastName = $this->calculateLastPage($data, $pageVar);

        return $this->template->replaceByPrefix(
            isset($this->wrapperTpl) ? $this->wrapperTpl : '@common.views/pagination/wrapper',
            [
                'num' => $num,
                'pageFirst' => $pageFirstName,
                'pageLast' => $pageLastName,
                'pageCurrent' => Helper::getValue($data['pageCurrent']),
                'countMore' => Helper::getValue($data['countMore'])
            ]
        );
    }

    protected function calculateArray()
    {
        $this->array = Helper::getValue($this->array);
        if (!empty($this->call)) {
            $this->array = $this->callFunction($this->call);
        }
    }

    /**
     * Calculate url args
     *
     * @return bool
     */
    protected function calculateArgs()
    {
        if (empty($this->pageArgs)) {
            return true;
        }
        if (is_string($this->pageArgs)) {
            parse_str(
                String::trimSpaces($this->pageArgs),
                $this->pageArgs
            );
        }
        if (empty($this->pageArgs) || !is_array($this->pageArgs)) {
            return false;
        }
        foreach ($this->pageArgs as $key => $val) {
            if (empty($key) || empty($val)) {
                continue;
            }
            $this->pageArgs[$key] = strip_tags($val);
        }

        return true;
    }

    protected function calculateNum(array $data, $pageVar)
    {
        $result = '';
        foreach ($data['pageDisplay'] as $num) {
            $this->pageArgs[$pageVar] = $num;
            $url = $this->Rock->url->addArgs($this->pageArgs)->addAnchor($this->pageAnchor)->get();
            /**
             * for active page
             */
            if ((int)$data['pageCurrent'] === (int)$num) {
                $result .=
                    $this->template->replaceByPrefix(
                        isset($this->pageActiveTpl) ? $this->pageActiveTpl
                            : '@common.views/pagination/numActive',
                        [
                            'num' => $num,
                            'url' => $url
                        ]
                    );
                continue;
            }
            /**
             * for default page
             */
            $result .=
                $this->template->replaceByPrefix(
                    isset($this->pageNumTpl) ? $this->pageNumTpl : '@common.views/pagination/num',
                    [
                        'num' => $num,
                        'url' => $url
                    ]
                );
        }

        return $result;
    }

    protected function calculateFirstPage(array $data, $pageVar)
    {
        if (!$pageFirst = (int)$data['pageFirst']) {
            return null;
        }
        $pageFirstName = !empty($this->pageFirstName) ? $this->pageFirstName : Rock::t('pageFirst');
        $this->pageArgs[$pageVar] = $pageFirst;


        return $this->template->replaceByPrefix(
            isset($this->pageFirstTpl) ? $this->pageFirstTpl : '@common.views/pagination/first',
            [
                'url' => $this->Rock->url
                        ->addArgs($this->pageArgs)
                        ->addAnchor($this->pageAnchor)
                        ->get(),
                'pageFirstName' => $pageFirstName
            ]
        );
    }

    protected function calculateLastPage(array $data, $pageVar)
    {
        if (!$pageLast = (int)$data['pageLast']) {
            return null;
        }
        $pageLastName = !empty($this->pageLastName) ? $this->pageLastName : Rock::t('pageLast');
        $this->pageArgs[$pageVar] = $pageLast;

        return $this->template->replaceByPrefix(
            isset($this->pageLastTpl) ? $this->pageLastTpl : '@common.views/pagination/last',
            [
                'url' => $this->Rock->url
                        ->addArgs($this->pageArgs)
                        ->addAnchor($this->pageAnchor)
                        ->get(),
                'pageLastName' => $pageLastName
            ]
        );
    }
}