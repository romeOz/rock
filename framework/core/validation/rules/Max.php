<?php
namespace rock\validation\rules;

class Max extends AbstractRule
{
    public $maxValue;
    public $inclusive;

    public function __construct($maxValue, $inclusive=false)
    {
        $this->maxValue = $maxValue;
        $this->inclusive = $inclusive;
    }

    public function validate($input)
    {
        if ($this->maxValue instanceof \DateTime && !$input instanceof \DateTime){
            try {
                $input = new \DateTime($input);
            } catch (\Exception $e){
                return false;
            }
        }

        if ($this->inclusive) {
            return $input <= $this->maxValue;
        } else {
            return $input < $this->maxValue;
        }
    }
}

