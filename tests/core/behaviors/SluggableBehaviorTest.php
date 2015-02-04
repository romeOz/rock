<?php

namespace rockunit\core\behaviors;

use rock\behaviors\SluggableBehavior;
use rockunit\core\db\DatabaseTestCase;
use rockunit\core\db\models\ActiveRecord;

/**
 * Unit test for {@see \rock\behaviors\SluggableBehavior}.
 * @see SluggableBehavior
 *
 * @group behaviors
 * @group db
 */
class SluggableBehaviorTest extends DatabaseTestCase
{
    protected function setUp()
    {
        parent::setUp();
        ActiveRecord::$connection = $this->getConnection();
        $columns = [
            'id' => 'pk',
            'name' => 'string',
            'slug' => 'string',
            'category_id' => 'integer',
        ];
        $command = $this->getConnection()->createCommand();
        $command->dropTable('test_slug', true)->execute();
        $command->createTable('test_slug', $columns)->execute();
    }

    // Tests :

    public function testSlug()
    {
        $model = new ActiveRecordSluggable();
        $model->name = 'test name';
        $model->validate();

        $this->assertEquals('test-name', $model->slug);
    }

    /**
     * @depends testSlug
     */
    public function testSlugSeveralAttributes()
    {
        $model = new ActiveRecordSluggable();
        $model->getBehavior('sluggable')->attribute = ['name', 'category_id'];

        $model->name = 'test';
        $model->category_id = 10;

        $model->validate();
        $this->assertEquals('test-10', $model->slug);
    }

    /**
     * @depends testSlug
     */
    public function testUniqueByIncrement()
    {
        $name = 'test name';

        $model = new ActiveRecordSluggableUnique();
        $model->name = $name;
        $model->save();

        $model = new ActiveRecordSluggableUnique();
        $model->sluggable->uniqueSlugGenerator = 'increment';
        $model->name = $name;
        $model->save();

        $this->assertEquals('test-name-2', $model->slug);
    }

    /**
     * @depends testUniqueByIncrement
     */
    public function testUniqueByCallback()
    {
        $name = 'test name';

        $model = new ActiveRecordSluggableUnique();
        $model->name = $name;
        $model->save();

        $model = new ActiveRecordSluggableUnique();
        $model->sluggable->uniqueSlugGenerator = function($baseSlug, $iteration) {return $baseSlug . '-callback';};
        $model->name = $name;
        $model->save();

        $this->assertEquals('test-name-callback', $model->slug);
    }

    /**
     * @depends testSlug
     */
    public function testUpdateUnique()
    {
        $name = 'test name';

        $model = new ActiveRecordSluggableUnique();
        $model->name = $name;
        $model->save();

        $model->save();
        $this->assertEquals('test-name', $model->slug);

        $model = ActiveRecordSluggableUnique::find()->one();
        $model->save();
        $this->assertEquals('test-name', $model->slug);

        $model->name = 'test-name';
        $model->save();
        $this->assertEquals('test-name', $model->slug);
    }
}

/**
 * Test Active Record class with {@see \rock\behaviors\SluggableBehavior} behavior attached.
 *
 * @property integer $id
 * @property string $name
 * @property string $slug
 * @property integer $category_id
 *
 * @property SluggableBehavior $sluggable
 */
class ActiveRecordSluggable extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'sluggable' => [
                'class' => SluggableBehavior::className(),
                'attribute' => 'name',
            ],
        ];
    }

    public static function tableName()
    {
        return 'test_slug';
    }

    /**
     * @return SluggableBehavior
     */
    public function getSluggable()
    {
        return $this->getBehavior('sluggable');
    }
}

class ActiveRecordSluggableUnique extends ActiveRecordSluggable
{
    public function behaviors()
    {
        return [
            'sluggable' => [
                'class' => SluggableBehavior::className(),
                'attribute' => 'name',
                'ensureUnique' => true,
            ],
        ];
    }
}