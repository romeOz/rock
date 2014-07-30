<?php

namespace rockunit\extensions\sphinx;

use rock\Rock;
use rock\sphinx\IndexSchema;
use rockunit\common\CommonTrait;

/**
 * @group search
 * @group sphinx
 * @group db
 */
class SchemaTest extends SphinxTestCase
{
    use CommonTrait;

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        static::clearRuntime();
    }

    public function testFindIndexNames()
    {
        $schema = $this->getConnection()->schema;

        $indexes = $schema->getIndexNames();
        $this->assertContains('article_index', $indexes);
        $this->assertContains('category_index', $indexes);
        $this->assertContains('rt_index', $indexes);
    }

    public function testGetIndexSchemas()
    {
        $schema = $this->getConnection()->schema;

        $indexes = $schema->getIndexSchemas();
        $this->assertEquals(count($schema->getIndexNames()), count($indexes));
        foreach ($indexes as $index) {
            $this->assertInstanceOf(IndexSchema::className(), $index);
        }
    }

    public function testGetNonExistingIndexSchema()
    {
        $this->assertNull($this->getConnection()->schema->getIndexSchema('non_existing_index'));
    }

    public function testSchemaRefresh()
    {
        $schema = $this->getConnection()->schema;

        $schema->db->enableSchemaCache = true;
        $cache = Rock::$app->cache;
        $cache->enabled();
        $schema->db->schemaCache = $cache;
        $noCacheIndex = $schema->getIndexSchema('rt_index', true);
        $cachedIndex = $schema->getIndexSchema('rt_index', true);
        $this->assertEquals($noCacheIndex, $cachedIndex);
        $cache->flush();
    }

    public function testGetPDOType()
    {
        $values = [
            [null, \PDO::PARAM_NULL],
            ['', \PDO::PARAM_STR],
            ['hello', \PDO::PARAM_STR],
            [0, \PDO::PARAM_INT],
            [1, \PDO::PARAM_INT],
            [1337, \PDO::PARAM_INT],
            [true, \PDO::PARAM_BOOL],
            [false, \PDO::PARAM_BOOL],
            [$fp = fopen(__FILE__, 'rb'), \PDO::PARAM_LOB],
        ];

        $schema = $this->getConnection()->schema;

        foreach ($values as $value) {
            $this->assertEquals($value[1], $schema->getPdoType($value[0]));
        }
        fclose($fp);
    }

    public function testIndexType()
    {
        $schema = $this->getConnection()->schema;

        $index = $schema->getIndexSchema('article_index');
        $this->assertEquals('local', $index->type);
        $this->assertFalse($index->isRuntime);

        $index = $schema->getIndexSchema('rt_index');
        $this->assertEquals('rt', $index->type);
        $this->assertTrue($index->isRuntime);
    }
}
