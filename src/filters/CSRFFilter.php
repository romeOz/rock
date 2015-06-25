<?php

namespace rock\filters;


use rock\csrf\CSRF;
use rock\helpers\ArrayHelper;
use rock\helpers\Instance;
use rock\request\Request;
use rock\response\Response;

class CSRFFilter extends ActionFilter
{
    /**
     * @var CSRF|string|array the CSRF instance.
     */
    public $csrf = 'csrf';
    /**
     * @var Response|string|array the response to be sent. If not set, the `response` application component will be used.
     */
    public $response = 'response';
    /**
     * @var Request
     */
    public $request = 'request';
    /** @var  string */
    public $compare;
    public $verbs = ['POST', 'PUT', 'DELETE', 'PATH'];
    public $validate = true;
    public $throwException = false;
    /**
     * @throws \rock\helpers\InstanceException
     */
    public function init()
    {
        $this->csrf = Instance::ensure($this->csrf, '\rock\csrf\CSRF');
        $this->csrf->enableCsrfValidation = $this->validate;
        $this->response = Instance::ensure($this->response, '\rock\response\Response');
        $this->request = Instance::ensure($this->request, '\rock\request\Request');
        $this->verbs = (array)$this->verbs;
        if ($this->verbs === ['*']) {
            $this->verbs = ['GET', 'POST', 'PUT', 'HEAD', 'OPTIONS', 'PATH'];
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!$this->validate || !$this->request->isMethods($this->verbs)) {
            return true;
        }
        $this->compare = $this->getCompare();
        if (!$this->csrf->valid($this->compare)) {
            $this->response->setStatusCode(403, 'Invalid CSRF-token.');
            if ($this->throwException === true) {
                throw new CsrfFilterException('Invalid CSRF-token.');
            }
            return false;
        }
        return true;
    }

    protected function getCompare()
    {
        if (isset($this->compare)) {
            return $this->compare;
        }

        if ($globals = $this->getGlobalsVars()) {
            if ($global = ArrayHelper::searchByKey($this->csrf->csrfParam, $globals)) {
                return current($global);
            }
        }

        return $this->compare;
    }

    protected function getGlobalsVars()
    {
        if ($this->request->isPost() && in_array('POST', $this->verbs, true)) {
            return Request::postAll();
        }

        if ($this->request->isGet() && in_array('GET', $this->verbs, true)) {
            return Request::getAll();
        }

        if ($this->request->isPut() && in_array('PUT', $this->verbs, true)) {
            return Request::putAll();
        }

        if ($this->request->isDelete() && in_array('DELETE', $this->verbs, true)) {
            return Request::deleteAll();
        }

        return [];
    }
}