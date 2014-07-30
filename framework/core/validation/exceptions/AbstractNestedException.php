<?php
namespace rock\validation\exceptions;

use RecursiveIteratorIterator;
use RecursiveTreeIterator;
use rock\validation\ExceptionIterator;

class AbstractNestedException extends ValidationException
{
    const ITERATE_TREE = 1;
    const ITERATE_ALL = 2;

    protected $related = array();

    public function addRelated(ValidationException $related)
    {
        $this->related[spl_object_hash($related)] = $related;

        return $this;
    }

    public function findMessages(array $paths)
    {
        $messages = array();

        foreach ($paths as $key => $value) {
            $numericKey = is_numeric($key);
            $path = $numericKey ? $value : $key;

            $e = $this->findRelated($path);

            if (is_object($e) && !$numericKey) {
                $e->setTemplate($value);
            }

            //$path = str_replace('.', '_', $path);
            $messages[$path] = $e ? $e->getMainMessage() : '';
        }

        return $messages;
    }

    public function findAllMessages()
    {
        $iterator = $this->getIterator(true);
        $messages = [];
        $totalMessages = [];
        /** @var $e ValidationException */
        foreach ($iterator as $e) {
            $customMessages = $e->getParam('_messages');
            if (!in_array($e->getId(), ['allOf', 'oneOf', 'noneOf', 'key'])) {
                $totalMessages[$e->getId()] =
                    isset($customMessages[$e->getId()])
                        ? $customMessages[$e->getId()]
                        : $e->getMainMessage();
            }
            if ($e->getParam('reference')) {
                $name = $e->getParam('reference');
            }
            if ($iterator->callHasChildren() === false && isset($name)) {

                if (isset($messages[$name][$e->getId()])) {
                    if (!is_array($messages[$name][$e->getId()])) {
                        $messages[$name][$e->getId()] = [$messages[$name][$e->getId()]];
                    }
                    $messages[$name][$e->getId()][] =
                        isset($customMessages[$name][$e->getId()])
                            ? $customMessages[$name][$e->getId()]
                            : $e->getMainMessage();
                } else {
                    $messages[$name][$e->getId()] =
                        isset($customMessages[$name][$e->getId()])
                            ? $customMessages[$name][$e->getId()]
                            : $e->getMainMessage();
                }

                //unset($name);
            }
        }

        return $messages ? : $totalMessages;
    }



    public function findRelated($path)
    {
        $path = explode('.', $path);

        if (empty($path)) {
            return $this;
        }
        foreach ($path as $key => $value) {
            if ($value === 'last' && isset($path[$key-1])) {
                $value = $this->getRelatedLast($path[$key-1]);
                break;
            }
            $value = $this->getRelatedByName($value);
        }

        return isset($value) ? $value : $this;
    }

    public function getIterator($full=false, $mode=self::ITERATE_ALL)
    {
        $exceptionIterator = new ExceptionIterator($this, $full);

        if ($mode == self::ITERATE_ALL) {
            return new RecursiveIteratorIterator($exceptionIterator, 1);
        } else {
            return new RecursiveTreeIterator($exceptionIterator);
        }
    }

    public function getFullMessage()
    {
        $message = array();
        $iterator = $this->getIterator(false, self::ITERATE_TREE);
        foreach ($iterator as $m) {
            $message[] = $m;
        }

        return implode(PHP_EOL, $message);
    }

    public function getRelated($full=false)
    {
        if (!$full && 1 === count($this->related)
            && current($this->related) instanceof AbstractNestedException) {
            return current($this->related)->getRelated();
        } else {
            return $this->related;
        }
    }

    public function getRelatedByName($name)
    {
        foreach ($this->getIterator(true) as $e) {
            if ($e->getId() === $name || $e->getName() === $name) {
                return $e;
            }
        }

        return false;
    }


    /**
     * @param $name
     * @return bool|ValidationException
     */
    public function getRelatedLast($name)
    {
        $target = null;
        $iterator = $this->getIterator(true);
        /** @var $e ValidationException */
        foreach ($iterator as $e) {

            if ($e->getParam('reference') === $name) {
                $target = $name;
            }
            if ($iterator->callHasChildren() === false && isset($target)) {

                return $e;
            }
        }

        return false;
    }

    public function setRelated(array $relatedExceptions)
    {
        foreach ($relatedExceptions as $related) {
            $this->addRelated($related);
        }

        return $this;
    }
}

