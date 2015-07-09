<?php

namespace rockunit\rbac;

use rock\rbac\DBManager;

/**
 * @group rbac
 * @group db
 */
class RBACWithDbTest extends RBACTest
{
    public function setUp()
    {
        parent::setUp();
        $this->rbac = new DBManager(['connection' => $this->getConnection()]);
    }
}
 