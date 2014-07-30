<?php

namespace rockunit\core\helpers;

use rock\base\ObjectTrait;
use rock\helpers\Instance;

class Post1
{
    public $id = 23;
    public $title = 'tt';
}

class Post2
{
    use ObjectTrait;

    public $id = 123;
    public $content = 'test';
    private $secret = 's';

    public function getSecret()
    {
        return $this->secret;
    }
}

class Post3
{
    use ObjectTrait {
        ObjectTrait::__construct as parentConstruct;
    }

    public $id = 33;
    public $subObject;

    public function __construct(array $configs = [])
    {
        $this->parentConstruct($configs);
        $this->subObject = new Post2();
    }
}

/**
 * @group base
 * @group helpers
 */
class InstanceHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testToArray()
    {
        $object = new Post1;
        $this->assertEquals(get_object_vars($object), Instance::toArray($object));
        $object = new Post2;
        $this->assertEquals(get_object_vars($object), Instance::toArray($object));
        $object1 = new Post1;
        $object2 = new Post2;
        $this->assertEquals(
            [
                get_object_vars($object1),
                get_object_vars($object2),
            ],
            Instance::toArray(
                [
                    $object1,
                    $object2,
                ]
            )
        );
        $object = new Post2;
        $this->assertEquals(
            [
                'id' => 123,
                'secret' => 's',
                '_content' => 'test',
                'length' => 4,
            ],
            Instance::toArray(
                $object,
                [
                    $object->className() => [
                        'id', 'secret',
                        '_content' => 'content',
                        'length' => function ($post) {
                                return strlen($post->content);
                            }
                    ]
                ]
            )
        );
        $object = new Post3();
        $this->assertEquals(get_object_vars($object), Instance::toArray($object, [], false));
        $this->assertEquals(
            [
                'id' => 33,
                'subObject' => [
                    'id' => 123,
                    'content' => 'test',
                ],
            ],
            Instance::toArray($object)
        );
    }


    public function testPrepareArray()
    {
    }
}
 