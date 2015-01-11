<?php


namespace rockunit\core\helpers\mocks;

/**
 * Forces Inflector::slug to use PHP even if intl is available
 */
class InflectorMock extends \rock\helpers\Inflector
{
    /**
     * @inheritdoc
     */
    protected static function hasIntl()
    {
        return false;
    }
}