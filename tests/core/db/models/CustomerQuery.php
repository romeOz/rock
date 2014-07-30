<?php

namespace rockunit\core\db\models;

use rock\db\ActiveQuery;

/**
 * CustomerQuery
 */
class CustomerQuery extends ActiveQuery
{
    public function active()
    {
        $this->andWhere('status=1');

        return $this;
    }
}
