<?php
namespace rock\validation\rules;

use rock\validation\exceptions\ValidationException;
use rock\validation\Validatable;

class Domain extends AbstractComposite
{
    /**
     * @var Validatable
     */
    protected $tld;
    /**
     * @var Validatable[]
     */
    protected $checks = array();
    protected $otherParts;

    public function __construct($tldCheck=true)
    {
        $this->checks[] = new NoWhitespace();
        $this->checks[] = new Contains('.');
        $this->checks[] = new OneOf(new Not(new Contains('--')),
                                    new AllOf(new StartsWith('xn--'),
                                              new Callback(function ($str) {
                                                  return substr_count($str, "--") == 1;
                                              })));
        $this->checks[] = new Length(3, null);
        $this->TldCheck($tldCheck);
        $this->otherParts = new AllOf(
            new Alnum('-'),
            new Not(new StartsWith('-'))
        );
    }

    public function tldCheck($do=true)
    {
        if($do === true) {
            $this->tld = new Tld();
        } else {
            $this->tld = new AllOf(
                    new Not(new StartsWith('-')),
                    new NoWhitespace(),
                    new Length(2, null)
                );
        }

        return true;
    }

    public function validate($input)
    {
        if (!empty($this->placeholders) || !empty($this->model)) {
            return $this->validateWithModelOrPlaceholder($input);
        }

        foreach ($this->checks as $chk)
            if (!$chk->validate($input))
                return false;

        if (count($parts = explode('.', $input)) < 2
            || !$this->tld->validate(array_pop($parts)))
            return false;

        foreach ($parts as $p)
            if (!$this->otherParts->validate($p))
                return false;

        return true;
    }

    public function assert($input, $enableProvideError = true)
    {
        $exceptions = array();
        foreach ($this->checks as $check) {
            $this->collectAssertException($exceptions, $check, $input);
        }

        if (count($parts = explode('.', $input)) >= 2) {
            $this->collectAssertException($exceptions, $this->tld, array_pop($parts));
        }

        foreach ($parts as $p) {
            $this->collectAssertException($exceptions, $this->otherParts, $p);
        }

        if (count($exceptions)) {
            $exception = $this->reportError($input)->setRelated($exceptions);
            if ($enableProvideError === true) {
                $this->provideErrorAll($exception);
            }

            throw $exception;
        }

        return true;
    }

    protected function collectAssertException(&$exceptions, Validatable $validator, $input)
    {
        try {
            $validator->assert($input, false);
        } catch (ValidationException $e) {
            $exceptions[] = $e;
        }
    }

    public function check($input)
    {
        foreach ($this->checks as $check)
            $check->check($input);

        if (count($parts = explode('.', $input)) >= 2) {
            $this->tld->check(array_pop($parts));
        }

        foreach ($parts as $part) {
            $this->otherParts->check($part);
        }

        return true;
    }
}

