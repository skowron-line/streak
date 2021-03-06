<?php

/**
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Streak\Infrastructure\Event\Converter;

use PHPUnit\Framework\TestCase;
use Streak\Domain\Event;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @covers \Streak\Infrastructure\Event\Converter\CompositeConverter
 */
class CompositeConverterTest extends TestCase
{
    /**
     * @var Event\Converter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $converter1;

    /**
     * @var Event\Converter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $converter2;

    /**
     * @var Event\Converter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $converter3;

    /**
     * @var Event|\PHPUnit_Framework_MockObject_MockObject
     */
    private $message;

    protected function setUp()
    {
        $this->converter1 = $this->getMockBuilder(Event\Converter::class)->getMockForAbstractClass();
        $this->converter2 = $this->getMockBuilder(Event\Converter::class)->getMockForAbstractClass();
        $this->converter3 = $this->getMockBuilder(Event\Converter::class)->getMockForAbstractClass();

        $this->message = $this->getMockBuilder(Event::class)->getMockForAbstractClass();
    }

    public function testConvertingToArray()
    {
        $data = ['test' => 'data'];

        $this->converter1
            ->expects($this->once())
            ->method('objectToArray')
            ->with($this->message)
            ->willThrowException(new Event\Exception\ConversionToArrayNotPossible($this->message))
        ;

        $this->converter2
            ->expects($this->once())
            ->method('objectToArray')
            ->with($this->message)
            ->willReturn($data)
        ;

        $this->converter3
            ->expects($this->never())
            ->method('objectToArray')
        ;

        $composite = new CompositeConverter();
        $composite->addConverter($this->converter1);
        $composite->addConverter($this->converter2);
        $composite->addConverter($this->converter3);

        $result = $composite->objectToArray($this->message);

        $this->assertSame($data, $result);
    }

    public function testConvertingToArrayWithUnexpectedException()
    {
        $unexpectedException = new \InvalidArgumentException('Unexpected Exception.');
        $expectedException = new Event\Exception\ConversionToArrayNotPossible($this->message, $unexpectedException);

        $this->converter1
            ->expects($this->once())
            ->method('objectToArray')
            ->with($this->message)
            ->willThrowException(new Event\Exception\ConversionToArrayNotPossible($this->message))
        ;

        $this->converter2
            ->expects($this->once())
            ->method('objectToArray')
            ->with($this->message)
            ->willThrowException($unexpectedException)
        ;

        $this->converter3
            ->expects($this->never())
            ->method('objectToArray')
        ;

        $composite = new CompositeConverter();
        $composite->addConverter($this->converter1);
        $composite->addConverter($this->converter2);
        $composite->addConverter($this->converter3);

        $this->expectExceptionObject($expectedException);
        $composite->objectToArray($this->message);
    }

    public function testUnsuccessfulConvertingToArray()
    {
        $expectedException = new Event\Exception\ConversionToArrayNotPossible($this->message);

        $this->converter1
            ->expects($this->once())
            ->method('objectToArray')
            ->with($this->message)
            ->willThrowException(new Event\Exception\ConversionToArrayNotPossible($this->message))
        ;

        $this->converter2
            ->expects($this->once())
            ->method('objectToArray')
            ->with($this->message)
            ->willThrowException(new Event\Exception\ConversionToArrayNotPossible($this->message))
        ;

        $this->converter3
            ->expects($this->once())
            ->method('objectToArray')
            ->with($this->message)
            ->willThrowException(new Event\Exception\ConversionToArrayNotPossible($this->message))
        ;

        $composite = new CompositeConverter();
        $composite->addConverter($this->converter1);
        $composite->addConverter($this->converter2);
        $composite->addConverter($this->converter3);

        $this->expectExceptionObject($expectedException);
        $composite->objectToArray($this->message);
    }

    public function testConvertingToMessage()
    {
        $class = 'class';
        $data = ['test' => 'data'];

        $this->converter1
            ->expects($this->once())
            ->method('arrayToObject')
            ->with($data)
            ->willThrowException(new Event\Exception\ConversionToObjectNotPossible($data))
        ;

        $this->converter2
            ->expects($this->once())
            ->method('arrayToObject')
            ->with($data)
            ->willReturn($this->message)
        ;

        $this->converter3
            ->expects($this->never())
            ->method('arrayToObject')
        ;

        $composite = new CompositeConverter();
        $composite->addConverter($this->converter1);
        $composite->addConverter($this->converter2);
        $composite->addConverter($this->converter3);

        $result = $composite->arrayToObject($data);

        $this->assertSame($this->message, $result);
    }

    public function testConvertingToMessageWithUnexpectedException()
    {
        $class = 'class';
        $data = ['test' => 'data'];

        $unexpectedException = new \InvalidArgumentException('Unexpected Exception.');
        $expectedException = new Event\Exception\ConversionToObjectNotPossible($data, $unexpectedException);

        $this->converter1
            ->expects($this->once())
            ->method('arrayToObject')
            ->with($data)
            ->willThrowException(new Event\Exception\ConversionToObjectNotPossible($data))
        ;

        $this->converter2
            ->expects($this->once())
            ->method('arrayToObject')
            ->with($data)
            ->willThrowException($unexpectedException)
        ;

        $this->converter3
            ->expects($this->never())
            ->method('arrayToObject')
        ;

        $composite = new CompositeConverter();
        $composite->addConverter($this->converter1);
        $composite->addConverter($this->converter2);
        $composite->addConverter($this->converter3);

        $this->expectExceptionObject($expectedException);
        $composite->arrayToObject($data);
    }

    public function testUnsuccessfulConvertingToMessage()
    {
        $class = 'class';
        $data = ['test' => 'data'];

        $expectedException = new Event\Exception\ConversionToObjectNotPossible($data);

        $this->converter1
            ->expects($this->once())
            ->method('arrayToObject')
            ->with($data)
            ->willThrowException(new Event\Exception\ConversionToObjectNotPossible($data))
        ;

        $this->converter2
            ->expects($this->once())
            ->method('arrayToObject')
            ->with($data)
            ->willThrowException(new Event\Exception\ConversionToObjectNotPossible($data))
        ;

        $this->converter3
            ->expects($this->once())
            ->method('arrayToObject')
            ->with($data)
            ->willThrowException(new Event\Exception\ConversionToObjectNotPossible($data))
        ;

        $composite = new CompositeConverter();
        $composite->addConverter($this->converter1);
        $composite->addConverter($this->converter2);
        $composite->addConverter($this->converter3);

        $this->expectExceptionObject($expectedException);

        $composite->arrayToObject($data);
    }
}
