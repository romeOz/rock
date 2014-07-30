<?php

namespace rock\validation;

use rock\base\ComponentsTrait;
use rock\validation\exceptions\AllOfException;
use rock\validation\exceptions\ComponentException;
use rock\validation\rules\AllOf;
use rock\validation\rules\Not;

/**
 * @method static Validation allOf()
 * @method static Validation alnum(string $additionalChars = null)
 * @method static Validation alpha(string $additionalChars = null)
 * @method static Validation alwaysInvalid()
 * @method static Validation alwaysValid()
 * @method static Validation arr()
 * @method static Validation attribute(string $reference, Validatable $validator = null, bool $mandatory = true)
 * @method static Validation base()
 * @method static Validation between(mixed $min = null, mixed $max = null, bool $inclusive = false)
 * @method static Validation bool()
 * @method static Validation captcha(mixed $compareTo, bool $compareIdentical=false)
 * @method static Validation call()
 * @method static Validation callback(mixed $callback)
 * @method static Validation charset(array $charset)
 * @method static Validation cnh()
 * @method static Validation cnpj()
 * @method static Validation consonant(string $additionalChars = null)
 * @method static Validation contains(mixed $containsValue, bool $identical = false)
 * @method static Validation confirm(mixed $compareTo, bool $compareIdentical=false)
 * @method static Validation countryCode()
 * @method static Validation cpf()
 * @method static Validation creditCard()
 * @method static Validation date(string $format = null)
 * @method static Validation digit(string $additionalChars = null)
 * @method static Validation directory()
 * @method static Validation domain()
 * @method static Validation each(Validatable $itemValidator = null, Validatable $keyValidator = null)
 * @method static Validation email()
 * @method static Validation endsWith(mixed $endValue, bool $identical = false)
 * @method static Validation equals(mixed $compareTo, bool $compareIdentical=false)
 * @method static Validation even()
 * @method static Validation exists()
 * @method static Validation file()
 * @method static Validation fileExtensions($extensions, bool $checkExtensionByMimeType = true)
 * @method static Validation fileMimeTypes($mimeTypes)
 * @method static Validation fileSizeMax(int $maxValue, bool $inclusive = false) *
 * @method static Validation fileSizeMin(int $minValue, bool $inclusive = false)
 * @method static Validation fileSizeBetween(int $min = null, int $max = null, bool $inclusive = false)
 * @method static Validation float()
 * @method static Validation graph(string $additionalChars = null)
 * @method static Validation in(array $haystack, bool $compareIdentical = false)
 * @method static Validation instance(string $instanceName)
 * @method static Validation int()
 * @method static Validation ip(array $ipOptions = null)
 * @method static Validation json()
 * @method static Validation key(string $reference, Validatable $referenceValidator = null, bool $mandatory = true)
 * @method static Validation leapDate(mixed $format)
 * @method static Validation leapYear()
 * @method static Validation length(int $min=null, int $max=null, bool $inclusive = true)
 * @method static Validation lowercase()
 * @method static Validation macAddress()
 * @method static Validation max(int $maxValue, bool $inclusive = false)
 * @method static Validation min(int $minValue, bool $inclusive = false)
 * @method static Validation minimumAge(int $age)
 * @method static Validation multiple(int $multipleOf)
 * @method static Validation negative()
 * @method static Validation noneOf()
 * @method static Validation not(Validatable $rule = null)
 * @method static Validation notEmpty()
 * @method static Validation noWhitespace()
 * @method static Validation nullValue()
 * @method static Validation numeric()
 * @method static Validation object()
 * @method static Validation odd()
 * @method static Validation oneOf()
 * @method static Validation perfectSquare()
 * @method static Validation phone()
 * @method static Validation positive()
 * @method static Validation primeNumber()
 * @method static Validation prnt(string $additionalChars = null)
 * @method static Validation punct(string $additionalChars = null)
 * @method static Validation readable()
 * @method static Validation regex($regex)
 * @method static Validation roman()
 * @method static Validation sf(string $name, array $params = null)
 * @method static Validation slug()
 * @method static Validation space(string $additionalChars = null)
 * @method static Validation startsWith(mixed $startValue, bool $identical = false)
 * @method static Validation string()
 * @method static Validation symbolicLink()
 * @method static Validation token(string $token)
 * @method static Validation tld()
 * @method static Validation uploaded()
 * @method static Validation uppercase()
 * @method static Validation version()
 * @method static Validation vowel()
 * @method static Validation when(Validatable $if, Validatable $then, Validatable $when)
 * @method static Validation writable()
 * @method static Validation xdigit(string $additionalChars = null)
 * @method static Validation zend(mixed $validator, array $params = null)
 */
class Validation extends AllOf
{
    use ComponentsTrait {
        ComponentsTrait::__construct as parentConstruct;
        //ObjectTrait::__call as parentCall;
    }
    /**
     * @var \Closure[]
     */
    protected static $groups;

    public function __construct($configs = [])
    {
        $this->parentConstruct($configs);
        call_user_func_array(['parent', '__construct'], array_slice(func_get_args(), 1));
        //parent::__construct();
    }

    public function init()
    {
        if (static::$locale instanceof \Closure) {
            call_user_func(static::$locale, $this);
        }
    }

    public static function __callStatic($ruleName, $arguments)
    {
        if ('allOf' === $ruleName) {
            return static::buildRule($ruleName, $arguments);
        }

        /** @var self $validator */
        $validator = new static;

        return $validator->__call($ruleName, $arguments);
    }

    /**
     * @param       $ruleSpec
     * @param array $arguments
     * @return Validatable
     * @throws ComponentException
     */
    public static function buildRule($ruleSpec, array $arguments=array())
    {
        if ($ruleSpec instanceof Validatable) {
            return $ruleSpec;
        }

        try {
            $validatorFqn = 'rock\\validation\\rules\\' . ucfirst($ruleSpec);
            $validatorClass = new \ReflectionClass($validatorFqn);
            $validatorInstance = $validatorClass->newInstanceArgs(
                $arguments
            );

            return $validatorInstance;
        } catch (\ReflectionException $e) {
            throw new ComponentException($e->getMessage());
        }
    }

    public function __call($method, $arguments)
    {
        if ('not' === $method) {
            return $arguments ? static::buildRule($method, $arguments) : new Not($this);
        }

        if (isset(static::$groups[$method])) {

            return call_user_func(static::$groups[$method], $arguments);
        }

        if (isset($method{4}) &&
            substr($method, 0, 4) == 'base' && preg_match('@^base([0-9]{1,2})$@', $method, $match)) {
            return $this->addRule(static::buildRule('base', array($match[1])));
        }

        return $this->addRule(static::buildRule($method, $arguments));
    }

    /**
     * Add group
     * @param string    $name - name of group
     * @param callable $validation
     * @return $this
     */
    public function addGroup($name, \Closure $validation)
    {
        static::$groups[$name] = $validation;
        return $this;
    }

    /**
     * Remove group
     * @param $name
     */
    public function removeGroup($name)
    {
        unset(static::$groups[$name]);
    }

    /**
     * Remove groups
     */
    public function removeAllGroups()
    {
        static::$groups = [];
    }

    public function reportError($input, array $extraParams=array())
    {
        $exception = new AllOfException;
        $input = AllOfException::stringify($input);
        $name = $this->getName() ? : "\"$input\"";
        $params = array_merge(
            $extraParams, get_object_vars($this), get_class_vars(__CLASS__), $this->params
        );
        $exception->configure($name, $params);
        if (!is_null($this->template)) {
            $exception->setTemplate($this->template);
        }

        return $exception;
    }

    /**
     * Create instance validator
     *
     * @static
     * @return static
     */
    public static function create()
    {
        $ref = new \ReflectionClass(__CLASS__);

        return $ref->newInstanceArgs(func_get_args());
    }
}