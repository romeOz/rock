<?php

namespace rock\authclient\snippets;


use rock\authclient\Collection;
use rock\di\Container;
use rock\helpers\Json;
use rock\template\Snippet;

class AuthClient extends Snippet
{
    public $clients = [];
    /**
     * name of template
     * @var string
     */
    public $tpl;
    /**
     * name of wrapper template
     * @var string
     */
    public $wrapperTpl;
    public $autoEscape = false;
    /** @var  Collection */
    protected $authClientCollection;

    public function init()
    {
        parent::init();

        $this->authClientCollection = Container::load('authClientCollection');
    }

    public function get()
    {
        if (!empty($this->tpl)) {
            return $this->renderWithTpl($this->authClientCollection);
        }

        return $this->renderWithoutTpl($this->authClientCollection);
    }

    protected function renderWithTpl(Collection $collection)
    {
        $content = '';
        $placeholders = [];
        foreach ($this->clients as $client) {
            $placeholders['clientName'] = $client;
            $placeholders['url'] = $collection->getClient($client)->getAuthorizationUrl();
            $content .= $this->template->replaceByPrefix($this->tpl, $placeholders);
        }
        return isset($this->wrapperTpl) ? $this->renderWrapperTpl($content) : $content;
    }

    protected function renderWithoutTpl(Collection $collection)
    {
        $result = [];
        foreach ($this->clients as $client) {
            $result[$client] = $collection->getClient($client)->getAuthorizationUrl();
        }

        return Json::encode($result);
    }

    /**
     * Inserting content into wrapper template
     *
     * @param string $value - content
     * @return string
     */
    protected function renderWrapperTpl($value)
    {
        $placeholders['output'] = $value;
        $value = $this->template->replaceByPrefix($this->wrapperTpl);
        $this->template->removePlaceholder('output');

        return $value;
    }
}