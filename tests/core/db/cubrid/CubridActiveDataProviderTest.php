<?php
namespace rockunit\core\db\cubrid;

use rockunit\core\db\ActiveDataProviderTest;

/**
 * @group db
 * @group cubrid
 * @group data
 */
class CubridActiveDataProviderTest extends ActiveDataProviderTest
{
    public $driverName = 'cubrid';
}
