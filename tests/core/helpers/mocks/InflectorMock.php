<?php


namespace rockunit\core\helpers\mocks;

use rock\helpers\BaseInflector;

/**
 * Forces Inflector::slug to use PHP even if intl is available
 */
class InflectorMock extends BaseInflector
{
    /**
     * @inheritdoc
     */
    protected static function hasIntl()
    {
        return false;
    }
}