<?php

namespace rockunit\core\di;

use rock\base\ObjectTrait;
use rock\di\Container;

/**
 * @group base
 */
class ContainerTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $container = new Container;
        unset($container['bar'], $container['foo'], $container['baz']);
    }

    public function testGetData()
    {
        $container = new Container;
        $container['bar'] = ['class' => Bar::className(), 'singleton' => true];
        $this->assertSame($container->get('bar')['class'], Bar::className());
    }

    public function testRemove()
    {
        $container = new Container;
        $container['bar'] = ['class' => Bar::className(), 'singleton' => true];
        $this->assertTrue(Container::has('bar'));
        $container->remove('bar');
        $this->assertNull($container->get('bar'));

        $container->bar = ['class' => Bar::className()];
        $this->assertTrue(Container::has('bar'));
        unset($container->bar);
        $this->assertFalse(Container::has('bar'));
    }

    public function testLoad()
    {
        $container = new Container;
        $container['bar'] = ['class' => Bar::className(), 'singleton' => true];
        $this->assertTrue($container::load(['class' => Bar::className()]) instanceof Bar);

        $container->bar = ['class' => Bar::className(), 'singleton' => true];
        $this->assertTrue(Container::load(['class' => Bar::className()]) instanceof Bar);

        // Singleton
        $container['bar'] = ['class' => Bar::className(), 'singleton' => true];
        $this->assertSame($container::load('bar'), $container::load('bar'));

        // new instance
        $container['bar'] = ['class' => Bar::className()];
        $this->assertTrue($container::load('bar') !== $container::load('bar'));

        // as Closure
        $container['bar'] =
            function($data = null){
                $this->assertSame($data[0], 'test');
                return new Bar();
            };
        $this->assertTrue(Container::load('test','bar') instanceof Bar);
        $this->assertTrue(Container::load('test',['class' => 'bar']) instanceof Bar);
    }

    public function testNewCustomArgsConstruct()
    {
        $foo = new Foo(new Bar, null, null, ['baz' => new Baz]);
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz instanceof Baz);
        $this->assertNull($foo->baz->bar);

        $foo = Container::load(['class'=>Foo::className(), 'baz' => new Baz]);
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz instanceof Baz);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertNull($foo->param);

        $foo = Container::load(new Bar, 'test', ['class'=>Foo::className(), 'baz' => new Baz]);
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz instanceof Baz);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertSame('test', $foo->param);

        // inline class
        $foo = Container::load(new Bar, ['test'], new Baz, Foo::className());
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertSame(['test'], $foo->param);

    }

    public function testIoCCustomArgsConstruct()
    {
        Container::add('foo', ['class'=>Foo::className(), 'baz' => new Baz]);
        $foo = Container::load(['class'=>Foo::className(), 'baz' => new Baz]);
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz instanceof Baz);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertNull($foo->param);

        Container::add('foo', ['class'=>Foo::className(), 'singleton' =>true, 'baz' => new Baz]);
        $foo = Container::load(new Bar, 'test', ['class'=>Foo::className(), 'baz' => new Baz]);
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz instanceof Baz);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertSame('test', $foo->param);

        $foo = Container::load(new Bar, 'test', ['class'=>Foo::className(), 'baz' => Container::load(Baz::className())]);
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz instanceof Baz);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertSame('test', $foo->param);

        // inline class
        Container::add('foo', ['class'=>Foo::className(), 'singleton' =>true, 'baz' => new Baz]);
        $foo = Container::load(new Bar, ['test'], new Baz, Foo::className());
        $this->assertTrue($foo->bar instanceof Bar);
        $this->assertTrue($foo->baz2 instanceof Baz);
        $this->assertSame(['test'], $foo->param);
    }

    public function testExceptionIsThrown()
    {
        try {
            Container::load(Test::className());
        } catch (\Exception $e) {
            $this->assertSame($e->getMessage(), 'Unknown class: rockunit\core\di\BarInterface.');
        }

        $test = Container::load(new Bar, Test::className());
        $this->assertTrue($test->bar instanceof Bar);

        Container::load(Test2::className());
    }

    public static function tearDownAfterClass()
    {
        Container::removeMulti(['foo', 'bar', 'baz']);
    }
}


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