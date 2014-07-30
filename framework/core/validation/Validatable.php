<?php
namespace rock\validation;

use rock\validation\exceptions\AbstractNestedException;
use rock\base\Model;

/** Interface for validation rules */
interface Validatable
{
    public function assert($input);

    public function check($input);

    public function getName();

    /**
     * @param       $input
     * @param array $relatedExceptions
     * @return AbstractNestedException
     */
    public function reportError($input, array $relatedExceptions=array());

    /**
     * @param $name
     * @return $this
     */
    public function setName($name);

    /**
     * @param array $params
     * @return $this
     */
    public function setParams(array $params);

    /**
     * @param array|string $placeholders
     * @return $this
     */
    public function setPlaceholders($placeholders);

    /**
     * @param array $messages
     * @return $this
     */
    public function setMessages(array $messages);

    /**
     * @param Model $model
     * @return $this
     */
    public function setModel(Model $model = null);

    public function setTemplate($template);

    public function validate($input);
}

