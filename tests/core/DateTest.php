<?php

namespace rockunit\core;


use rock\date\DateTime;
use rock\date\DateException;
use rock\date\locale\Ru;

/**
 * @group base
 */
class DateTimeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerData
     */
    public function testGetTimestamp($time)
    {
        $this->assertSame((new DateTime($time))->getTimestamp(), 595296000);
    }

    public function providerData()
    {
        return [
            ['1988-11-12'],
            [595296000],
            ['595296000']
        ];
    }

    public function testFormat()
    {
        $this->assertSame((new DateTime)->format('j  n  Y'), date('j  n  Y'));
        $this->assertSame((new DateTime)->format(), date('Y-m-d H:i:s'));
    }

    public function testDefaultFormat()
    {
        $this->assertSame((new DateTime)->isoDate(), date('Y-m-d'));
        $this->assertSame((new DateTime)->isoTime(), date('H:i:s'));
        $this->assertSame((new DateTime)->isoDatetime(), date('Y-m-d H:i:s'));

        // set default format
        $dateTime = new DateTime;
        $dateTime->setDefaultFormat('j  n  Y');
        $this->assertSame($dateTime->format(), date('j  n  Y'));

        // unknown format
        $this->setExpectedException(DateException::className());
        (new DateTime)->unknown();
    }

    public function testLocal()
    {
        $dateTime = new DateTime('1988-11-12');
        $dateTime->setLocale('ru');
        $this->assertSame($dateTime->format('j  F  Y'), '12  ноября  1988');
        $this->assertSame($dateTime->format('j  M  Y'), '12  ноя  1988');
        $this->assertSame($dateTime->format('j  l  Y'), '12  суббота  1988');
        $this->assertSame($dateTime->format('j  D  Y'), '12  Сб  1988');
        $this->assertTrue($dateTime->getLocale() instanceof Ru);

        $this->assertNotEmpty($dateTime->getLocale()->getFormats());
        $this->assertNotEmpty($dateTime->getLocale()->getMonths());
        $this->assertNotEmpty($dateTime->getLocale()->getWeekDays());
        $this->assertNotEmpty($dateTime->getLocale()->getShortWeekDays());
    }

    public function testAddCustomFormat()
    {
        $datetime = new DateTime('1988-11-12');
        $datetime->addCustomFormat('shortDate', 'j / F / Y');
        $this->assertSame($datetime->shortDate(), '12 / November / 1988');
        $this->assertArrayHasKey('shortDate', $datetime->getCustomFormats());
    }

    public function testAddFormatOption()
    {
        $datetime = new DateTime('1988-11-12');
        $datetime->addFormatOption('ago', function (DateTime $datetime) {
            return floor((time() - $datetime->getTimestamp()) / 86400) . ' days ago';
        });
        $ago = floor((time() - $datetime->getTimestamp()) / 86400);
        $this->assertSame($datetime->format('d F Y, ago'), "12 November 1988, {$ago} days ago");

        // duplicate
        $datetime->addFormatOption('ago', function (DateTime $datetime) {
            return floor((time() - $datetime->getTimestamp()) / 86400) . ' days ago';
        });
    }

    public function testDiff()
    {
        $dateTime = new DateTime('1988-11-12');
        $this->assertSame($dateTime->diff(time())->w, (int)floor($dateTime->diff(time())->_days / 7));

        $dateInterval = $dateTime->diff('1988-11-12');
        $this->assertSame($dateInterval->w, (int)floor($dateInterval->_days / 7) * -1);

        $dateInterval = $dateTime->diff('1988-11-12', true);
        $this->assertSame($dateInterval->w, (int)floor($dateInterval->_days / 7));
    }

    /**
     * @dataProvider providerIsTrue
     */
    public function testIsDateTrue($value)
    {
        $this->assertTrue(DateTime::is($value));
    }

    public function providerIsTrue()
    {
        return [
            ['1988-11-12'],
            ['595296000'],
            ['-595296000'],
            [595296000],
            [-595296000],
            [3.14],
            ['3.14']
        ];
    }

    /**
     * @dataProvider providerIsFalse
     */
    public function testIsDateFalse($value)
    {
        $this->assertFalse(DateTime::is($value));
    }

    public function providerIsFalse()
    {
        return [
            ['foo'],
            [''],
            [null],
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider providerIsTimestampTrue
     */
    public function testIsTimestampTrue($value)
    {
        $this->assertTrue(DateTime::isTimestamp($value));
    }

    public function providerIsTimestampTrue()
    {
        return [
            ['595296000'],
            ['-595296000'],
            [595296000],
            [-595296000],
        ];
    }

    /**
     * @dataProvider providerIsTimestampFalse
     */
    public function testIsTimestampFalse($value)
    {
        $this->assertFalse(DateTime::isTimestamp($value));
    }

    public function providerIsTimestampFalse()
    {
        return [
            ['foo'],
            [''],
            [null],
            [true],
            [false],
            ['1988-11-12'],
            ['3.14'],
            [3.14],
        ];
    }

    public function testTimezone()
    {
        $this->assertNotEquals(
            (new DateTime('now', 'America/Chicago'))->isoDatetime(),
            (new DateTime('now', new \DateTimeZone('Europe/Volgograd')))->isoDatetime()
        );

        $this->assertNotEquals(
            (new DateTime('2008-12-02 10:21:00'))->convertTimezone('America/Chicago')->isoDatetime(),
            (new DateTime('2008-12-02 10:21:00'))->convertTimezone(new \DateTimeZone('Europe/Volgograd'))->isoDatetime()
        );
    }
}