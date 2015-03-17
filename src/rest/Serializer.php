<?php

namespace rock\rest;

use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\components\Arrayable;
use rock\components\Model;
use rock\db\ActiveDataPagination;
use rock\db\ActiveDataProvider;
use rock\helpers\ArrayHelper;
use rock\helpers\Instance;
use rock\helpers\Link;
use rock\request\Request;
use rock\response\Response;

/**
 * Serializer converts resource objects and collections into array representation.
 *
 * Serializer is mainly used by REST controllers to convert different objects into array representation
 * so that they can be further turned into different formats, such as JSON, XML, by response formatters.
 *
 * The default implementation handles resources as {@see \rock\components\Model} objects and collections as objects
 * implementing {@see \rock\db\ActiveDataProvider}. You may override {@see \rock\rest\Serializer::serialize()} to handle more types.
 */
class Serializer implements ObjectInterface
{
    use ObjectTrait;

    /**
     * @var string the name of the query parameter containing the information about which fields should be returned
     * for a {@see \rock\components\Model} object. If the parameter is not provided or empty, the default set of fields as defined
     * by {@see \rock\components\Model::fields()} will be returned.
     */
    public $fieldsParam = 'fields';
    /**
     * @var string the name of the query parameter containing the information about which fields should be returned
     * in addition to those listed in {@see \rock\rest\Serializer::$fieldsParam} for a resource object.
     */
    public $excludeParam = 'exclude';
    /**
     * @var string the name of the HTTP header containing the information about total number of data items.
     * This is used when serving a resource collection with pagination.
     */
    public $totalCountHeader = 'X-Pagination-Total-Count';
    /**
     * @var string the name of the HTTP header containing the information about total number of pages of data.
     * This is used when serving a resource collection with pagination.
     */
    public $pageCountHeader = 'X-Pagination-Page-Count';
    /**
     * @var string the name of the HTTP header containing the information about the current page number (1-based).
     * This is used when serving a resource collection with pagination.
     */
    public $currentPageHeader = 'X-Pagination-Current-Page';
    /**
     * @var string the name of the HTTP header containing the information about the number of data items in each page.
     * This is used when serving a resource collection with pagination.
     */
    public $perPageHeader = 'X-Pagination-Per-Page';
    /**
     * @var string the name of the envelope (e.g. `items`) for returning the resource objects in a collection.
     * This is used when serving a resource collection. When this is set and pagination is enabled, the serializer
     * will return a collection in the following format:
     *
     * ```php
     * [
     *     'items' => [...],  // assuming collectionEnvelope is "items"
     *     '_links' => {  // pagination links as returned by Pagination::getLinks()
     *         'self' => '...',
     *         'next' => '...',
     *         'last' => '...',
     *     },
     *     '_meta' => {  // meta information as returned by Pagination::toArray()
     *         'totalCount' => 100,
     *         'pageCount' => 5,
     *         'currentPage' => 1,
     *         'perPage' => 20,
     *     },
     * ]
     * ```
     *
     * If this property is not set, the resource arrays will be directly returned without using envelope.
     * The pagination information as shown in `_links` and `_meta` can be accessed from the response HTTP headers.
     */
    public $collectionEnvelope;
    /**
     * @var Request the current request. If not set, the `request` application component will be used.
     */
    public $request= 'request';
    /**
     * @var Response the response to be sent. If not set, the `response` application component will be used.
     */
    public $response = 'response';

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->request = Instance::ensure($this->request, Request::className());
        $this->response = Instance::ensure($this->response, Response::className());
    }

    /**
     * Serializes the given data into a format that can be easily turned into other formats.
     * This method mainly converts the objects of recognized types into array representation.
     * It will not do conversion for unknown object types or non-object data.
     * The default implementation will handle {@see \rock\components\Model} and {@see \rock\db\ActiveDataProvider}.
     * You may override this method to support more object types.
     * @param mixed $data the data to be serialized.
     * @return mixed the converted data.
     */
    public function serialize($data)
    {
        if ($data instanceof Model && $data->hasErrors()) {
            return $this->serializeModelErrors($data);
        } elseif ($data instanceof Arrayable) {
            return $this->serializeModel($data);
        } elseif ($data instanceof ActiveDataProvider) {
            return $this->serializeDataProvider($data);
        } else {
            return $data;
        }
    }

    /**
     * @return array the names of the requested fields. The first element is an array
     * representing the list of default fields requested, while the second element is
     * an array of the extra fields requested in addition to the default fields.
     * @see Model::fields()
     * @see Model::extraFields()
     */
    protected function getRequestedFields()
    {
        $fields = Request::get($this->fieldsParam);
        $exclude = Request::get($this->excludeParam);

        return [
            preg_split('/\s*,\s*/', $fields, -1, PREG_SPLIT_NO_EMPTY),
            preg_split('/\s*,\s*/', $exclude, -1, PREG_SPLIT_NO_EMPTY),
        ];
    }

    /**
     * Serializes a data provider.
     * @param ActiveDataProvider $dataProvider
     * @return array the array representation of the data provider.
     */
    protected function serializeDataProvider($dataProvider)
    {
        $models = $this->serializeModels($dataProvider->get());

        if (($pagination = $dataProvider->getPagination()) !== false) {
            $this->addPaginationHeaders($pagination);
        }

        if ($this->request->isHead()) {
            return null;
        } elseif ($this->collectionEnvelope === null) {
            return $models;
        } else {
            $result = [
                $this->collectionEnvelope => $models,
            ];
            if ($pagination !== false) {
                return array_merge($result, $this->serializePagination($pagination));
            } else {
                return $result;
            }
        }
    }

    /**
     * Serializes a pagination into an array.
     * @param ActiveDataPagination $pagination
     * @return array the array representation of the pagination
     * @see addPaginationHeaders()
     */
    protected function serializePagination($pagination)
    {
        return [
            '_links' => Link::serialize($pagination->getLinks(true)),
            '_meta' => [
                'totalCount' => $pagination->totalCount,
                'pageCount' => $pagination->pageCount,
                'currentPage' => $pagination->getPage(),
                'perPage' => $pagination->limit,
            ],
        ];
    }

    /**
     * Adds HTTP headers about the pagination to the response.
     * @param ActiveDataPagination $pagination
     */
    protected function addPaginationHeaders($pagination)
    {
        $links = [];
        foreach ($pagination->getLinks(true) as $rel => $url) {
            $links[] = "<$url>; rel=$rel";
        }

        $this->response->getHeaders()
            ->set($this->totalCountHeader, $pagination->totalCount)
            ->set($this->pageCountHeader, $pagination->pageCount)
            ->set($this->currentPageHeader, $pagination->getPage())
            ->set($this->perPageHeader, $pagination->limit)
            ->set('Link', implode(', ', $links));
    }

    /**
     * Serializes a model object.
     * @param Arrayable $model
     * @return array the array representation of the model
     */
    protected function serializeModel($model)
    {
        if ($this->request->isHead()) {
            return null;
        } else {
            list ($fields, $exclude) = $this->getRequestedFields();
            return $model->toArray($fields, $exclude);
        }
    }

    /**
     * Serializes the validation errors in a model.
     * @param Model $model
     * @return array the array representation of the errors
     */
    protected function serializeModelErrors($model)
    {
        $this->response->setStatusCode(422, 'Data Validation Failed.');
        $result = [];
        foreach ($model->getFirstErrors() as $name => $message) {
            $result[] = [
                'field' => $name,
                'message' => $message,
            ];
        }

        return $result;
    }

    /**
     * Serializes a set of models.
     * @param array $models
     * @return array the array representation of the models
     */
    protected function serializeModels(array $models)
    {
        list ($fields, $expand) = $this->getRequestedFields();
        foreach ($models as $i => $model) {
            if ($model instanceof Arrayable) {
                $models[$i] = $model->toArray($fields, $expand);
            } elseif (is_array($model)) {
                $models[$i] = ArrayHelper::toArray($model);
            }
        }

        return $models;
    }
}