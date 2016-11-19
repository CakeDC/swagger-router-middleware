<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware;

use AvalancheDevelopment\SwaggerRouterMiddleware\Parser\ParserInterface;
use DateTime;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use ReflectionClass;

class ParameterParserTest extends PHPUnit_Framework_TestCase
{

    public function testInvokeCallsGetParser()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockParameter = [ 'something here' ];
        $mockRoute = '/some-route';
        $mockValue = 'some value';

        $mockParser = $this->createMock(ParserInterface::class);
        $mockParser->method('getValue')
            ->willReturn($mockValue);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getParser', 'castType' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('getParser')
            ->with($mockRequest, $mockParameter, $mockRoute)
            ->willReturn($mockParser);
        $parameterParser->method('castType')
            ->will($this->returnArgument(0));

        $parameterParser->__invoke($mockRequest, $mockParameter, $mockRoute);
    }

    public function testInvokeUsesParserFromGetParser()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockParameter = [ 'something here' ];
        $mockRoute = '/some-route';
        $mockValue = 'some value';

        $mockParser = $this->createMock(ParserInterface::class);
        $mockParser->expects($this->once())
            ->method('getValue')
            ->willReturn($mockValue);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getParser', 'castType' ])
            ->getMock();
        $parameterParser->method('getParser')
            ->willReturn($mockParser);
        $parameterParser->method('castType')
            ->will($this->returnArgument(0));

        $parameterParser->__invoke($mockRequest, $mockParameter, $mockRoute);
    }

    public function testInvokeReturnsDefaultValue()
    {
        $expectedValue = 'some default value';

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockParameter = [
            'default' => $expectedValue,
        ];
        $mockRoute = '/some-route';
        $mockValue = null;

        $mockParser = $this->createMock(ParserInterface::class);
        $mockParser->method('getValue')
            ->willReturn($mockValue);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getParser', 'castType' ])
            ->getMock();
        $parameterParser->method('getParser')
            ->willReturn($mockParser);
        $parameterParser->expects($this->once())
            ->method('castType')
            ->with($expectedValue, $mockParameter)
            ->will($this->returnArgument(0));

        $parameterParser->__invoke($mockRequest, $mockParameter, $mockRoute);
    }

    public function testInvokeUsesValueFromParser()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockParameter = [ 'something here' ];
        $mockRoute = '/some-route';
        $mockValue = 'some value';

        $mockParser = $this->createMock(ParserInterface::class);
        $mockParser->method('getValue')
            ->willReturn($mockValue);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getParser', 'castType' ])
            ->getMock();
        $parameterParser->method('getParser')
            ->willReturn($mockParser);
        $parameterParser->expects($this->once())
            ->method('castType')
            ->with($mockValue, $mockParameter)
            ->will($this->returnArgument(0));

        $parameterParser->__invoke($mockRequest, $mockParameter, $mockRoute);
    }

    public function testInvokeReturnsCastValue()
    {
        $expectedValue = 'some value';

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockParameter = [ 'something here' ];
        $mockRoute = '/some-route';
        $mockValue = 'some other value';

        $mockParser = $this->createMock(ParserInterface::class);
        $mockParser->method('getValue')
            ->willReturn($mockValue);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getParser', 'castType' ])
            ->getMock();
        $parameterParser->method('getParser')
            ->willReturn($mockParser);
        $parameterParser->method('castType')
            ->willReturn($expectedValue);

        $result = $parameterParser->__invoke($mockRequest, $mockParameter, $mockRoute);

        $this->assertSame($expectedValue, $result);
    }

    public function testCastTypeHandlesArray()
    {
        $parameter = [
            'items' => [
                'type' => 'string',
            ],
        ];
        $value = [
            123,
            456,
        ];
        $expectedValue = array_map(function ($row) {
            return (string) $row;
        }, $value);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedCastType = $reflectedParameterParser->getMethod('castType');
        $reflectedCastType->setAccessible(true);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getParameterType' ])
            ->getMock();
        $parameterParser->expects($this->exactly(3))
            ->method('getParameterType')
            ->with($this->isType('array'))
            ->will($this->onConsecutiveCalls('array', 'string', 'string'));

        $result = $reflectedCastType->invokeArgs(
            $parameterParser,
            [
                $value,
                $parameter,
            ]
        );

        $this->assertSame($expectedValue, $result);
    }

    public function testCastTypeHandlesBoolean()
    {
        $parameter = [
            'some value'
        ];
        $value = 'false';

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedCastType = $reflectedParameterParser->getMethod('castType');
        $reflectedCastType->setAccessible(true);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getParameterType' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('getParameterType')
            ->with($parameter)
            ->willReturn('boolean');

        $result = $reflectedCastType->invokeArgs(
            $parameterParser,
            [
                $value,
                $parameter,
            ]
        );

        $this->assertSame((boolean) $value, $result);
    }

    public function testCastTypeHandlesFile()
    {
        $this->markTestIncomplete('not yet implemented');
    }

    public function testCastTypeHandlesInteger()
    {
        $parameter = [
            'some value'
        ];
        $value = '245';

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedCastType = $reflectedParameterParser->getMethod('castType');
        $reflectedCastType->setAccessible(true);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getParameterType' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('getParameterType')
            ->with($parameter)
            ->willReturn('integer');

        $result = $reflectedCastType->invokeArgs(
            $parameterParser,
            [
                $value,
                $parameter,
            ]
        );

        $this->assertSame((int) $value, $result);
    }

    public function testCastTypeHandlesNumber()
    {
        $parameter = [
            'some value',
        ];
        $value = '3.141592';

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedCastType = $reflectedParameterParser->getMethod('castType');
        $reflectedCastType->setAccessible(true);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getParameterType' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('getParameterType')
            ->with($parameter)
            ->willReturn('number');

        $result = $reflectedCastType->invokeArgs(
            $parameterParser,
            [
                $value,
                $parameter,
            ]
        );

        $this->assertSame((float) $value, $result);
    }

    public function testCastTypeHandlesObject()
    {
        $parameter = [
            'some value',
        ];
        $value = (object) [
            'key' => 'value',
        ];

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedCastType = $reflectedParameterParser->getMethod('castType');
        $reflectedCastType->setAccessible(true);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'formatObject', 'getParameterType' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('formatObject')
            ->with(json_encode($value), $parameter)
            ->willReturn($value);
        $parameterParser->expects($this->once())
            ->method('getParameterType')
            ->with($parameter)
            ->willReturn('object');

        $result = $reflectedCastType->invokeArgs(
            $parameterParser,
            [
                json_encode($value),
                $parameter,
            ]
        );

        $this->assertEquals($value, $result);
    }

    public function testCastTypeHandlesString()
    {
        $parameter = [
            'some value',
        ];
        $value = 1337;

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedCastType = $reflectedParameterParser->getMethod('castType');
        $reflectedCastType->setAccessible(true);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'formatString', 'getParameterType' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('formatString')
            ->with($value, $parameter)
            ->will($this->returnArgument(0));
        $parameterParser->expects($this->once())
            ->method('getParameterType')
            ->with($parameter)
            ->willReturn('string');

        $result = $reflectedCastType->invokeArgs(
            $parameterParser,
            [
                $value,
                $parameter,
            ]
        );

        $this->assertSame((string) $value, $result);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Invalid parameter type value defined in swagger
     */
    public function testCastTypeBailsOnUnknownType()
    {
        $parameter = [
            'some value',
        ];

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedCastType = $reflectedParameterParser->getMethod('castType');
        $reflectedCastType->setAccessible(true);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getParameterType' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('getParameterType')
            ->with($parameter)
            ->willReturn('invalid');

        $reflectedCastType->invokeArgs(
            $parameterParser,
            [
                '',
                $parameter,
            ]
        );
    }

    public function testGetParameterTypeDefaultsToType()
    {
        $parameter = [
            'in' => 'path',
            'type' => 'good type',
            'schema' => [
                'type' => 'bad type',
            ],
        ];

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetParameterType = $reflectedParameterParser->getMethod('getParameterType');
        $reflectedGetParameterType->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetParameterType->invokeArgs(
            $parameterParser,
            [
                $parameter,
            ]
        );

        $this->assertEquals('good type', $result);
    }

    public function testGetParameterTypeBodyUsesSchemaType()
    {
        $parameter = [
            'in' => 'body',
            'type' => 'bad type',
            'schema' => [
                'type' => 'good type',
            ],
        ];

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetParameterType = $reflectedParameterParser->getMethod('getParameterType');
        $reflectedGetParameterType->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetParameterType->invokeArgs(
            $parameterParser,
            [
                $parameter,
            ]
        );

        $this->assertEquals('good type', $result);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Parameter type is not defined in swagger
     */
    public function testGetParameterTypeBailsOnEmptyType()
    {
        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetParameterType = $reflectedParameterParser->getMethod('getParameterType');
        $reflectedGetParameterType->setAccessible(true);

        $parameterParser = new ParameterParser;
        $reflectedGetParameterType->invokeArgs(
            $parameterParser,
            [[]]
        );
    }

    public function testFormatObjectHandlesObject()
    {
        $parameter = [
            'schema' => [
                'properties' => [
                    'key' => [
                        'some value',
                    ],
                ],
            ],
        ];

        $value = (object) [
            'key' => 'value',
        ];

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedFormatObject = $reflectedParameterParser->getMethod('formatObject');
        $reflectedFormatObject->setAccessible(true);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'castType' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('castType')
            ->with($value->key, $parameter['schema']['properties']['key'])
            ->willReturn('value');

        $result = $reflectedFormatObject->invokeArgs(
            $parameterParser,
            [
                $value,
                $parameter,
            ]
        );

        $this->assertEquals($value, $result);
    }

    public function testFormatObjectHandlesEncodedObject()
    {
        $parameter = [
            'schema' => [
                'properties' => [
                    'key' => [
                        'some value',
                    ],
                ],
            ],
        ];

        $value = (object) [
            'key' => 'value',
        ];

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedFormatObject = $reflectedParameterParser->getMethod('formatObject');
        $reflectedFormatObject->setAccessible(true);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'castType' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('castType')
            ->with($value->key, $parameter['schema']['properties']['key'])
            ->willReturn('value');

        $result = $reflectedFormatObject->invokeArgs(
            $parameterParser,
            [
                json_encode($value),
                $parameter,
            ]
        );

        $this->assertEquals($value, $result);
    }

    public function testFormatObjectHandlesPartiallyDefinedParameter()
    {
        $parameter = [
            'properties' => [
                'key' => [
                    'some value',
                ],
            ],
        ];

        $value = (object) [
            'key' => 'value',
        ];

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedFormatObject = $reflectedParameterParser->getMethod('formatObject');
        $reflectedFormatObject->setAccessible(true);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'castType' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('castType')
            ->with($value->key, $parameter['properties']['key'])
            ->willReturn('value');

        $result = $reflectedFormatObject->invokeArgs(
            $parameterParser,
            [
                json_encode($value),
                $parameter,
            ]
        );

        $this->assertEquals($value, $result);
    }

    public function testFormatObjectHandlesUndefinedParameterObject()
    {
        $parameter = [];

        $value = (object) [
            'key' => 'value',
        ];

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedFormatObject = $reflectedParameterParser->getMethod('formatObject');
        $reflectedFormatObject->setAccessible(true);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'castType' ])
            ->getMock();
        $parameterParser->expects($this->never())
            ->method('castType');

        $result = $reflectedFormatObject->invokeArgs(
            $parameterParser,
            [
                $value,
                $parameter,
            ]
        );

        $this->assertEquals($value, $result);
    }

    /**
     * @expectedException AvalancheDevelopment\Peel\HttpError\BadRequest
     * @expectedExceptionMessage Bad json object passed in as parameter
     */
    public function testFormatObjectBailsOnBadObject()
    {
        $value = 'some string';

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedFormatObject = $reflectedParameterParser->getMethod('formatObject');
        $reflectedFormatObject->setAccessible(true);

        $parameterParser = new ParameterParser;
        $reflectedFormatObject->invokeArgs(
            $parameterParser,
            [
                $value,
                [],
            ]
        );
    }

    public function testFormatObjectHandlesPartialDefinition()
    {
        $parameter = [
            'properties' => [
                'key' => [
                    'some value',
                ],
            ],
        ];

        $value = (object) [
            'key' => 'value',
        ];

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedFormatObject = $reflectedParameterParser->getMethod('formatObject');
        $reflectedFormatObject->setAccessible(true);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'castType' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('castType')
            ->with($value->key, $parameter['properties']['key'])
            ->willReturn('value');

        $result = $reflectedFormatObject->invokeArgs(
            $parameterParser,
            [
                $value,
                $parameter,
            ]
        );

        $this->assertEquals($value, $result);
    }

    public function testFormatStringIgnoresFormatlessParameter()
    {
        $value = 'some string';

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedFormatString = $reflectedParameterParser->getMethod('formatString');
        $reflectedFormatString->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedFormatString->invokeArgs(
            $parameterParser,
            [
                $value,
                []
            ]
        );

        $this->assertSame($value, $result);
    }

    public function testFormatStringHandlesDate()
    {
        $value = '2016-10-18';
        $expectedValue = DateTime::createFromFormat('Y-m-d', $value);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedFormatString = $reflectedParameterParser->getMethod('formatString');
        $reflectedFormatString->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedFormatString->invokeArgs(
            $parameterParser,
            [
                $value,
                [ 'format' => 'date' ],
            ]
        );

        $this->assertEquals($expectedValue, $result);
    }

    /**
     * @expectedException AvalancheDevelopment\Peel\HttpError\BadRequest
     * @expectedExceptionMessage Invalid date parameter passed in
     */
    public function testFormatStringHandlesDateFailures()
    {
        $value = 'invalid date';

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedFormatString = $reflectedParameterParser->getMethod('formatString');
        $reflectedFormatString->setAccessible(true);

        $parameterParser = new ParameterParser;
        $reflectedFormatString->invokeArgs(
            $parameterParser,
            [
                $value,
                [ 'format' => 'date' ],
            ]
        );
    }

    public function testFormatStringHandlesDateTime()
    {
        $value = '2016-10-18T+07:00';
        $expectedValue = new DateTime($value);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedFormatString = $reflectedParameterParser->getMethod('formatString');
        $reflectedFormatString->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedFormatString->invokeArgs(
            $parameterParser,
            [
                $value,
                [ 'format' => 'date-time' ],
            ]
        );

        $this->assertEquals($expectedValue, $result);
    }

    /**
     * @expectedException AvalancheDevelopment\Peel\HttpError\BadRequest
     * @expectedExceptionMessage Invalid date parameter passed in
     */
    public function testFormatStringHandlesDateTimeFailures()
    {
        $value = 'invalid date';

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedFormatString = $reflectedParameterParser->getMethod('formatString');
        $reflectedFormatString->setAccessible(true);

        $parameterParser = new ParameterParser;
        $reflectedFormatString->invokeArgs(
            $parameterParser,
            [
                $value,
                [ 'format' => 'date-time' ],
            ]
        );
    }

    public function testFormatStringIgnoresOnUnmatchedFormat()
    {
        $value = 'some value';

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedFormatString = $reflectedParameterParser->getMethod('formatString');
        $reflectedFormatString->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedFormatString->invokeArgs(
            $parameterParser,
            [
                $value,
                [ 'format' => 'random' ],
            ]
        );

        $this->assertSame($value, $result);
    }
}
