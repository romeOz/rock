<?php

namespace rockunit\core\bootstrap;

use rock\base\ObjectTrait;

class Foo
{
    use \rock\base\ObjectTrait {
        ObjectTrait::__set as parentSet;
        ObjectTrait::__get as parentGet;
    }

    public $baz;

    public static $staticTest = 'static property';
    public $test = 'property';



    public function setFoo($value)
    {
        $this->baz = $value;
    }

    public function getBar()
    {
        return 'bar';
    }

//    public function __set($name, $value)
//    {
//        var_dump('ppss');
//    }

}

class Bar extends Foo
{
    public $baz;


    public function __set($name, $value)
    {
        if ($name === 'exception') {
            $this->parentSet($name, $value);
            return null;
        }

        $this->baz = $value;
    }


    public function __get($name)
    {
        if ($name === 'exception') {
            $this->parentGet($name);
            return null;
        }

        return 'foo';
    }

}

class Baz
{
    use \rock\base\ObjectTrait {
        ObjectTrait::__construct as parentConstruct;
    }

    public static $pubProperty = 'pub';
    protected static $prtProperty = 7;
    private static $prvProperty = ['name' => 'baz', 'type' => ['name' => 'type']];

    public function __construct(Bar $bar, $config = [])
    {
        $this->parentConstruct($config);
    }


    /**
     * @param int $prtProperty
     */
    public static function setPrtProperty($prtProperty)
    {
        self::$prtProperty = $prtProperty;
    }

    /**
     * @param array $prvProperty
     */
    public static function setPrvProperty($prvProperty)
    {
        self::$prvProperty = $prvProperty;
    }

    /**
     * @param string $pubProperty
     */
    public static function setPubProperty($pubProperty)
    {
        self::$pubProperty = $pubProperty;
    }

    /**
     * @return string
     */
    public static function getPrvProperty()
    {
        return self::$prvProperty;
    }




    /**
     * @return string
     */
    public function getPrtProperty()
    {
        return static::$prtProperty;
    }
}



/**
 * @group base
 */
class BootstrapTest extends \PHPUnit_Framework_TestCase
{
    protected function tearDown()
    {
        Baz::setPubProperty('pub');
        Baz::setPrtProperty(7);
        Baz::setPrvProperty(['name' => 'baz', 'type' => ['name' => 'type']]);
    }

    public function testGetter()
    {
        $foo = new Foo();
        $this->assertEquals($foo->bar, 'bar');
        $bar = new Bar();
        $this->assertEquals($bar->any, 'foo');
    }

    /**
     * @expectedException \rock\exception\BaseException
     */
    public function testGetterThrowException()
    {
        $foo = new Foo();
        $foo->exception;
    }

    /**
     * @expectedException \rock\exception\BaseException
     */
    public function testBarGetterThrowException()
    {
        $bar = new Bar();
        $bar->exception;
    }

    public function testSetter()
    {
        $foo = new Foo();
        $foo->foo = 'baz';
        $this->assertEquals($foo->baz, 'baz');
        $bar = new Bar();
        $bar->any = 'any';
        $this->assertEquals($bar->baz, 'any');
    }

    /**
     * @expectedException \rock\exception\BaseException
     */
    public function testSetterThrowException()
    {
        $foo = new Foo();
        $foo->exception = 'exception';
    }

    /**
     * @expectedException \rock\exception\BaseException
     */
    public function testBarSetterThrowException()
    {
        $bar = new Bar();
        $bar->exception = 'exception';
    }



    public function testResetAllStaticProperties()
    {
        $baz = new Baz(new Bar());
        Baz::$pubProperty = 'test';
        $this->assertEquals(Baz::$pubProperty, 'test');
        $baz->resetStatic();
        $this->assertNull(Baz::$pubProperty);
        $this->assertInternalType('int', $baz->getPrtProperty());
        $this->assertEquals($baz->getPrtProperty(), 0);
        $this->assertInternalType('array', $baz->getPrvProperty());
        $this->assertEquals($baz->getPrvProperty(), []);
    }


    public function testResetStaticProperty()
    {
        $baz = new Baz(new Bar(), []);
        Baz::$pubProperty = 'test';
        $this->assertEquals(Baz::$pubProperty, 'test');
        $baz->resetStatic('pubProperty');
        $this->assertNull(Baz::$pubProperty);
        $this->assertInternalType('int', $baz->getPrtProperty());
        $this->assertEquals($baz->getPrtProperty(), 7);
        $this->assertInternalType('array', $baz->getPrvProperty());
        $this->assertEquals($baz->getPrvProperty(), ['name' => 'baz', 'type' => ['name' => 'type']]);
    }

    public function testResetStaticPropertyByKeys()
    {
        $baz = new Baz(new Bar(), []);
        Baz::$pubProperty = 'test';
        $baz->resetStatic('prvProperty', ['type', 'name']);
        $this->assertEquals(Baz::$pubProperty, 'test');
        $this->assertInternalType('int', $baz->getPrtProperty());
        $this->assertEquals($baz->getPrtProperty(), 7);
        $this->assertInternalType('array', $baz->getPrvProperty());
        $this->assertEquals($baz->getPrvProperty(), ['name' => 'baz', 'type' => []]);
    }

//
//    public function testCallStaticGetter()
//    {
//        $bar = new Bar();
//        $this->assertEquals($bar->getStaticTest(), 'static property');
//    }
//
//    public function testCallSetter()
//    {
//        $bar = new Bar();
//        $this->assertEquals($bar->getTest(), 'property');
//    }
//
//    public function testCallStaticSetter()
//    {
//        $bar = new Bar();
//        $bar->setStaticTest('change static property');
//        $this->assertEquals($bar->getStaticTest(), 'change static property');
//    }
//
//    public function testCallGetter()
//    {
//        $bar = new Bar();
//        $bar->setTest(['change property']);
//        $this->assertEquals($bar->getTest(), ['change property']);
//    }
}
 