<?php

namespace rockunit\core\rbac;

use rock\rbac\DBManager;
use rock\rbac\RBAC;

/**
 * @group rbac
 * @group db
 */
class RBACWithDbTest extends RBACTest
{
    public function setUp()
    {
        parent::setUp();
        DBManager::$connection = $this->getConnection();
    }

    /**
     * @return RBAC
     */
    protected function getRBAC()
    {
        $rbac = new DBManager();
        //$rbac->refresh();
        return $rbac;
    }
}
 