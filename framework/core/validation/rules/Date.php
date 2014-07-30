<?php
namespace rock\validation\rules;

use DateTime;

class Date extends AbstractRule
{
    public $format = null;

    public function __construct($format=null)
    {
        $this->format = $format;
    }

    public function validate($input)
    {
        if ($input instanceof DateTime) {
            return true;
        } elseif (!is_string($input)) {
            return false;
        } elseif (is_null($this->format)) {
            return false !== strtotime($input);
        }
        $dateFromFormat = DateTime::createFromFormat($this->format, $input);

        if (DateTime::getLastErrors()['warning_count'] > 0) {
            return false;
        }
        return $dateFromFormat
               && $input === date($this->format, $dateFromFormat->getTimestamp());
    }
}

