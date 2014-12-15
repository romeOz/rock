<?php
namespace rock\db;


use rock\base\ComponentsTrait;
use rock\base\Model;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\helpers\Pagination;
use rock\request\Request;
use rock\response\Response;
use rock\Rock;
use rock\sanitize\Sanitize;

/**
 * ActiveDataProvider implements a data provider based on {@see \rock\db\Query} and {@see \rock\db\ActiveQuery}.
 *
 * ActiveDataProvider provides data by performing DB queries using {@see \rock\db\ActiveDataProvider::$query }.
 *
 * The following is an example of using ActiveDataProvider to provide ActiveRecord instances:
 *
 * ```php
 * $provider = new ActiveDataProvider([
 *     'query' => Post::find(),
 *     'pagination' => [
 *         'limit' => 20,
 *         'sort' => SORT_DESC,
 *         'pageLimit' => 5
 *     ],
 * ]);
 *
 * $provider->get(); // get the posts in the current page
 * $provider->getPagination(); // get data pagination
 * ```
 *
 * And the following example shows how to use ActiveDataProvider without ActiveRecord:
 *
 * ```php
 * $query = new Query;
 * $provider = new ActiveDataProvider([
 *     'query' => $query->from('post'),
 *     'pagination' => [
 *         'limit' => 20,
 *         'sort' => SORT_DESC,
 *         'pageLimit' => 5,
 *         'pageCurrent' => (int)$_GET['page'],
 *     ],
 * ]);
 *
 * $provider->get(); // get the posts in the current page
 * $provider->getPagination(); // get data pagination
 * ```
 *
 */
class ActiveDataProvider
{
    use ComponentsTrait;

    const PAGE_VAR   = Pagination::PAGE_VAR;
    /**
     * @var array|QueryInterface
     */
    public $query;

    /**
     * @var array
     */
    public $pagination;

    /**
     * @var callable
     */
    public $callback;

    public $only = [];
    public $exclude = [];
    public $expand = [];

    /**
     * @var array
     */
    protected $_pagination;
    /**
     * @var int
     */
    protected $totalCount = 0;

    public function init()
    {
        if (!isset($this->pagination['pageCurrent'])) {
            $this->pagination['pageCurrent'] = Request::get(self::PAGE_VAR, null, Sanitize::positive()->int());
        }
    }


    public function setQuery(QueryInterface $value)
    {
        $this->query = $value;

        return $this;
    }

    public function setArray(array $value)
    {
        $this->query = $value;

        return $this;
    }


    public function get(Connection $connection = null, $subAttributes = false)
    {
        if (empty($this->query)) {
            return [];
        }

        $result = [];
        if (is_array($this->query)) {
            $result = $this->prepareArray();
        } elseif ($this->query instanceof QueryInterface) {
            $result = $this->prepareModels($connection, $subAttributes);
        }

        return $this->prepareDataWithCallback($result);
    }

    public function setCallback(\Closure $callback)
    {
        $this->callback = $callback;

        return $this;
    }

    public function toArray(Connection $connection = null, $subAttributes = false)
    {
        if (empty($this->query)) {
            return [];
        }

        $firstElement = null;
        if (is_array($this->query)) {
            $data = $this->prepareArray();
        } elseif ($this->query instanceof QueryInterface) {
            $data = $this->prepareModels($connection, $subAttributes);
        } elseif ($this->query instanceof ActiveRecordInterface) {
            return $this->prepareDataWithCallback($this->query->toArray($this->only, $this->exclude, $this->expand));
        } else {
            throw new Exception('Var must be of type array or instances ActiveRecord.');
        }

        if (!is_array($data)) {
            throw new Exception('Var must be of type array or instances ActiveRecord.');
        }

        reset($data);
        $firstElement = current($data);
        // as ActiveRecord[]
        if (is_array($data) && $firstElement instanceof ActiveRecordInterface) {
            return $this->prepareDataWithCallback(
                array_map(
                    function(Model $value){
                        return $value->toArray($this->only, $this->exclude, $this->expand);
                    },
                    $data
                )
            );
        }

        // as Array
        if (ArrayHelper::depth($data, true) === 0) {
            return $this->prepareDataWithCallback(ArrayHelper::only($data, $this->only, $this->exclude));
        }

        if (!empty($this->only) || !empty($this->exclude)) {
            return $this->prepareDataWithCallback(
                array_map(
                    function($value){
                        return ArrayHelper::only($value, $this->only, $this->exclude);
                    },
                    $data
                )
            );
        }

        return $this->prepareDataWithCallback($data);

    }

    /**
     * Get data pagination
     *
     * @return array|null
     */
    public function getPagination()
    {
        return $this->_pagination;
    }


    public function setPagination($data)
    {
        $this->pagination = $data;

        return $this;
    }

    /**
     * Get total count items
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * @return array
     */
    protected function prepareArray()
    {
        if (!$this->totalCount = count($this->query)) {
            $this->totalCount = 0;
            return [];
        }

        if (empty($this->pagination)) {
            return $this->query;
        }
        $this->calculatePagination();
        return array_slice($this->query, $this->_pagination['offset'], $this->_pagination['limit'], true);
    }


    /**
     * @param Connection $connection
     * @param bool       $subAttributes
     * @return ActiveRecord|\rock\sphinx\ActiveRecord
     */
    protected function prepareModels(Connection $connection = null, $subAttributes = false)
    {
        if (!$this->totalCount = $this->calculateTotalCount($connection)) {
            return [];
        }
        $this->calculatePagination();
        $this->addHeaders($this->totalCount, $this->_pagination);
        return $this->query
            ->limit($this->_pagination['limit'])
            ->offset($this->_pagination['offset'])
            ->all($connection, $subAttributes);
    }

    protected function calculatePagination()
    {
        $this->_pagination = Pagination::get(
            $this->totalCount,
            $this->pagination['pageCurrent'],
            Helper::getValue($this->pagination['limit'], Pagination::LIMIT),
            Helper::getValue($this->pagination['sort'], Pagination::SORT),
            Helper::getValue($this->pagination['pageLimit'],Pagination::PAGE_LIMIT)
        );
        if (isset($this->pagination['pageVar'])) {
            $this->_pagination['pageVar'] = $this->pagination['pageVar'];
        }
    }

    protected function addHeaders($total, array $data)
    {
        $response = Rock::$app->response;
        if ($response->format == Response::FORMAT_HTML || empty($data)) {
            return;
        }

        $absoluteUrl = $this->Rock->url->removeAllArgs()->getAbsoluteUrl(true);
        $links = [];
        $links[] = "<{$absoluteUrl}?{$data['pageVar']}={$data['pageCurrent']}>; rel=self";
        $response->content['_links']['self'] = "{$absoluteUrl}?{$data['pageVar']}={$data['pageCurrent']}";
        if (!empty($data['pagePrev'])) {
            $links[] = "<{$absoluteUrl}?{$data['pageVar']}={$data['pagePrev']}>; rel=prev";
            $response->content['_links']['prev'] = "{$absoluteUrl}?{$data['pageVar']}={$data['pagePrev']}";
        }
        if (!empty($data['pageNext'])) {
            $links[] = "<{$absoluteUrl}?{$data['pageVar']}={$data['pageNext']}>; rel=next";
            $response->content['_links']['next'] = "{$absoluteUrl}?{$data['pageVar']}={$data['pageNext']}";
        }
        if (!empty($data['pageFirst'])) {
            $links[] = "<{$absoluteUrl}?{$data['pageVar']}={$data['pageFirst']}>; rel=first";
            $response->content['_links']['first'] = "{$absoluteUrl}?{$data['pageVar']}={$data['pageFirst']}";
        }
        if (!empty($data['pageLast'])) {
            $links[] = "<{$absoluteUrl}?{$data['pageVar']}={$data['pageLast']}>; rel=last";
            $response->content['_links']['last'] = "{$absoluteUrl}?{$data['pageVar']}={$data['pageLast']}";
        }

        $response->getHeaders()
            ->set('X-Pagination-Total-Count', $total)
            ->set('X-Pagination-Page-Count', $data['pageCount'])
            ->set('X-Pagination-Current-Page', $data['pageCurrent'])
            ->set('X-Pagination-Per-Page', $data['limit'])
            ->set('Link', implode(', ', $links));
    }

    /**
     * @inheritdoc
     */
    protected function calculateTotalCount(Connection $connection = null)
    {
        $query = clone $this->query;

        return (int)$query->limit(-1)
                          ->offset(-1)
                          ->orderBy([])
                          ->count('*', $connection);
    }

    protected function prepareDataWithCallback(array $data)
    {
        if (!$this->callback instanceof \Closure || empty($data)) {
            return $data;
        }
        foreach ($data as $name => $value) {
            $data[$name] = ArrayHelper::map($value, $this->callback);
        }

        return $data;
    }
}
