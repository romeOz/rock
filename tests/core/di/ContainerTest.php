<?php

namespace rockunit\core\di;


use rock\base\ObjectTrait;
use rock\di\Container;
use rock\Rock;

class Foo
{
    use \rock\base\ObjectTrait {
        ObjectTrait::__construct as parentConstruct;
    }

    public $baz;
    public $baz2;


    public $param;
    public $bar;

    public function __construct(Bar $bar, $param = null, Baz $baz = null, array $config = [])
    {
        $this->parentConstruct($config);
        $this->param = $param;
        $this->bar = $bar;
        $this->baz2 = $baz;
    }
}


class Baz implements BarInterface
{
    use ObjectTrait;

    public $bar;
}

interface BarInterface{

}

class Bar implements BarInterface
{
    use ObjectTrait;

    public static $staticFoo = '';

    public function setStatic()
    {
        static::$staticFoo = 'bar';
        return $this;
    }

    public function getStatic()
    {
        return static::$staticFoo;
    }
}

class Test
{
    use ObjectTrait;

    public $bar;
    public function __construct(BarInterface $bar, array $configs = [])
    {
        $this->setProperties($configs);
        $this->bar = $bar;
    }
}

class Test2
{
    use ObjectTrait;

    public function __construct(BarInterface $bar = null, array $configs = [])
    {
        $this->setProperties($configs);
    }
}

/**
 * @group base
 */
class ContainerTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        unset(Rock::$app->di['bar'], Rock::$app->di['foo'], Rock::$app->di['baz']);
    }

    public function testGetData()
    {
        Rock::$app->di['bar'] = ['class' => Bar::className(), 'singleton' => true];
        $this->assertSame(Rock::$app->di->get('bar')['class'], Bar::className());
    }

    public function testRemove()
    {
        Rock::$app->di['bar'] = ['class' => Bar::className(), 'singleton' => true];
        $this->assertTrue(Container::has('bar'));
        Rock::$app->di->remove('bar');
        $this->assertNull(Rock::$app->di->get('bar'));

        Rock::$app->di->bar = ['class' => Bar::className()];
        $this->assertTrue(Container::has('bar'));
        unset(Rock::$app->di->bar);
        $this->assertFalse(Container::has('bar'));
    }

    public function testLoad()
    {
        Rock::$app->di['bar'] = ['class' => Bar::className(), 'singleton' => true];
        $this->assertTrue(Rock::$app->di->load(['class' => Bar::className()]) instanceof Bar);

        Rock::$app->di->bar = ['class' => Bar::className(), 'singleton' => true];
        $this->assertTrue(Rock::factory(['class' => Bar::className()]) instanceof Bar);

        /** Singleton */
        Rock::$app->di['bar'] = ['class' => Bar::className(), 'singleton' => true];
        $this->assertSame(Rock::$app->bar, Rock::$app->bar);

        /** new instance */
        Rock::$app->di['bar'] = ['class' => Bar::className()];
        $this->assertTrue(Rock::$app->bar !== Rock::$app->bar);

        /** as Closure */
        Rock::$app->di['bar'] =
            function($data = null){
                $this->assertSame($data[0], 'test');
                return new Bar();
            };
        $this->assertTrue(Rock::factory('test','bar') instanceof Bar);
        $this->assertTrue(Rock::factory('test',['class' => 'bar']) instanceof Bar);
    }



    public function testNewCustomArgsConstruct()
    {
        $foo = new Foo(new Bar, null, null, ['baz' => new Baz]);
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz instanceof Baz);
        $this->assertNull($foo->baz->bar);

        $foo = Rock::factory(['class'=>Foo::className(), 'baz' => new Baz]);
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz instanceof Baz);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertNull($foo->param);

        $foo = Rock::factory(new Bar, 'test', ['class'=>Foo::className(), 'baz' => new Baz]);
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz instanceof Baz);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertSame($foo->param, 'test');

        /** inline class */
        $foo = Rock::factory(new Bar, ['test'], new Baz, Foo::className());
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertSame($foo->param, ['test']);

    }


    public function testIoCCustomArgsConstruct()
    {

        Container::add('foo', ['class'=>Foo::className(), 'baz' => new Baz]);
        $foo = Rock::factory(['class'=>Foo::className(), 'baz' => new Baz]);
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz instanceof Baz);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertNull($foo->param);

        Container::add('foo', ['class'=>Foo::className(), 'singleton' =>true, 'baz' => new Baz]);
        $foo = Rock::factory(new Bar, 'test', ['class'=>Foo::className(), 'baz' => new Baz]);
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz instanceof Baz);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertSame($foo->param, 'test');

        $foo = Rock::factory(new Bar, 'test', ['class'=>Foo::className(), 'baz' => Rock::factory(Baz::className())]);
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz instanceof Baz);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertSame($foo->param, 'test');


        /** inline class */
        Container::add('foo', ['class'=>Foo::className(), 'singleton' =>true, 'baz' => new Baz]);
        $foo = Rock::factory(new Bar, ['test'], new Baz, Foo::className());
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertSame($foo->param, ['test']);
    }



    public function testExceptionIsThrown()
    {
        try {
            Rock::factory(Test::className());
        } catch (\Exception $e) {
            $this->assertSame($e->getMessage(), 'Unknown class: rockunit\core\di\BarInterface.');
        }

        $test = Rock::factory(new Bar, Test::className());
        $this->assertTrue($test->bar instanceof Bar);

        Rock::factory(Test2::className());
    }


    public function testGetStaticProperty()
    {
        Rock::$app->di['bar'] = ['class' => Bar::className(), 'singleton' => true, 'staticFoo' => 'foo'];
        $this->assertEquals(Rock::$app->bar->getStatic(), 'foo');
        $this->assertEquals(Bar::$staticFoo, 'foo');
        Bar::$staticFoo = 'baz';
        $this->assertEquals(Rock::$app->bar->getStatic(), 'baz');
    }

    public static function tearDownAfterClass()
    {
        Container::removeMulti(['foo', 'bar', 'baz']);
    }
}
 