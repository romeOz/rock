<?php
namespace rock\validation\rules;

class Min extends AbstractRule
{
    public $inclusive;
    public $minValue;

    public function __construct($minValue, $inclusive=false)
    {
        $this->minValue = $minValue;
        $this->inclusive = $inclusive;
    }

    public function validate($input)
    {
        $minValue = $this->minValue;
        if ($minValue instanceof \DateTime && !$input instanceof \DateTime){
            try {
                $input = new \DateTime($input);
            } catch (\Exception $e){
                return false;
            }

        }

        if ($this->inclusive) {
            return $input >= $minValue;
        } else {
            return $input > $minValue;
        }
    }
}

