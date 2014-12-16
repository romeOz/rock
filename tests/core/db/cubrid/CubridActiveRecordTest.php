<?php
namespace rockunit\core\db\cubrid;

use rockunit\core\db\ActiveRecordTest;

/**
 * @group db
 * @group cubrid
 */
class CubridActiveRecordTest extends ActiveRecordTest
{
    public $driverName = 'cubrid';

    public function testCache()
    {
        $this->markTestSkipped('Skipped: '. __METHOD__);
    }
}
