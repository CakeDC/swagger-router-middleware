<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use ReflectionClass;

class ParameterParserTest extends PHPUnit_Framework_TestCase
{

    public function testImplementsLoggerAwareInterface()
    {
        $router = new ParameterParser;

        $this->assertInstanceOf(LoggerAwareInterface::class, $router);
    }

    public function testConstructSetsNullLogger()
    {
        $logger = new NullLogger;

        $router = new ParameterParser;

        $this->assertAttributeEquals($logger, 'logger', $router);
    }

    public function testInvokeHandlesQueryParameter()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $parameter = [
            'in' => 'query',
            'type' => 'string',
        ];
        $route = '/some-route';
        $value = 'some value';

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getQueryValue' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('getQueryValue')
            ->with($mockRequest, $parameter)
            ->willReturn($value);

        $result = $parameterParser($mockRequest, $parameter, $route);

        $this->assertEquals($value, $result);
    }

    public function testInvokeHandlesHeaderParameter()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $parameter = [
            'in' => 'header',
            'type' => 'string',
        ];
        $route = '/some-route';
        $value = 'some value';

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getHeaderValue' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('getHeaderValue')
            ->with($mockRequest, $parameter)
            ->willReturn($value);

        $result = $parameterParser($mockRequest, $parameter, $route);

        $this->assertEquals($value, $result);
    }

    public function testInvokeHandlesPathParameter()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $parameter = [
            'in' => 'path',
            'type' => 'string',
        ];
        $route = '/some-route';
        $value = 'some value';

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getPathValue' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('getPathValue')
            ->with($mockRequest, $parameter, $route)
            ->willReturn($value);

        $result = $parameterParser($mockRequest, $parameter, $route);

        $this->assertEquals($value, $result);
    }

    public function testInvokeHandlesFormParameter()
    {
        $this->markTestIncomplete('not yet implemented');
    }

    public function testInvokeHandlesBodyParameter()
    {
        $this->markTestIncomplete('not yet implemented');
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage invalid parameter type
     */
    public function testInvokeBailsOnInvalidParameterType()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $parameter = [ 'in' => 'some type' ];
        $route = '/some-route';

        $parameterParser = new ParameterParser;
        $parameterParser($mockRequest, $parameter, $route);
    }

    public function testInvokeReturnsDefaultValue()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $parameter = [
            'in' => 'path',
            'default' => 'some default value',
            'type' => 'string',
        ];
        $route = '/some-route';

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getPathValue' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('getPathValue')
            ->with($mockRequest, $parameter, $route);

        $result = $parameterParser($mockRequest, $parameter, $route);

        $this->assertEquals($parameter['default'], $result);
    }

    public function testGetQueryValueReturnsNullIfUnmatched()
    {
        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getQuery')
            ->willReturn('some_variable=bar');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetQueryValue = $reflectedParameterParser->getMethod('getQueryValue');
        $reflectedGetQueryValue->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetQueryValue->invokeArgs($parameterParser, [
            $mockRequest,
            [ 'name' => 'other_variable' ],
        ]);

        $this->assertNull($result);
    }

    public function testGetQueryValueReturnsValueIfMatched()
    {
        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getQuery')
            ->willReturn('some_variable=value');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetQueryValue = $reflectedParameterParser->getMethod('getQueryValue');
        $reflectedGetQueryValue->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetQueryValue->invokeArgs($parameterParser, [
            $mockRequest,
            [
                'name' => 'some_variable',
                'type' => 'string',
            ],
        ]);

        $this->assertEquals('value', $result);
    }

    public function testGetQueryValueReturnsExplodedValueIfMatched()
    {
        $parameter = [
            'name' => 'some_variable',
            'type' => 'array',
        ];

        $value = [
            'some-value',
            'some-other-value',
        ];

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getQuery')
            ->willReturn('some_variable=value');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetQueryValue = $reflectedParameterParser->getMethod('getQueryValue');
        $reflectedGetQueryValue->setAccessible(true);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'explodeValue' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('explodeValue')
            ->with('value', $parameter)
            ->willReturn($value);

        $result = $reflectedGetQueryValue->invokeArgs($parameterParser, [
            $mockRequest,
            $parameter,
        ]);

        $this->assertEquals($value, $result);
    }

    public function testGetHeaderValueReturnsNullIfUnmatched()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getHeaders')
            ->willReturn([
                'Some-Header' => [ 'value' ],
            ]);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetHeaderValue = $reflectedParameterParser->getMethod('getHeaderValue');
        $reflectedGetHeaderValue->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetHeaderValue->invokeArgs($parameterParser, [
            $mockRequest,
            [ 'name' => 'Other-Header' ],
        ]);

        $this->assertNull($result);
    }

    public function testGetHeaderValueReturnsSingleValueIfMatched()
    {
        $headerValue = 'some_value';

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getHeaders')
            ->willReturn([
                'Some-Header' => [ $headerValue ],
            ]);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetHeaderValue = $reflectedParameterParser->getMethod('getHeaderValue');
        $reflectedGetHeaderValue->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetHeaderValue->invokeArgs($parameterParser, [
            $mockRequest,
            [
                'name' => 'Some-Header',
                'type' => 'string',
            ],
        ]);

        $this->assertEquals($headerValue, $result);
    }

    public function testGetHeaderValueReturnsMultipleValuesIfMatched()
    {
        $headerValue = [
            'first_value',
            'second_value',
            'third_value',
        ];

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getHeaders')
            ->willReturn([
                'Some-Header' => $headerValue,
            ]);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetHeaderValue = $reflectedParameterParser->getMethod('getHeaderValue');
        $reflectedGetHeaderValue->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetHeaderValue->invokeArgs($parameterParser, [
            $mockRequest,
            [
                'name' => 'Some-Header',
                'type' => 'array',
            ],
        ]);

        $this->assertEquals($headerValue, $result);
    }

    public function testGetPathValueReturnsNullIfUnmatched()
    {
        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')
            ->willReturn('/path');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetPathValue = $reflectedParameterParser->getMethod('getPathValue');
        $reflectedGetPathValue->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetPathValue->invokeArgs($parameterParser, [
            $mockRequest,
            [ 'name' => 'id' ],
            '/path/{id}',
        ]);

        $this->assertNull($result);
    }

    public function testGetPathValueReturnsValueIfMatched()
    {
        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')
            ->willReturn('/path/1234');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetPathValue = $reflectedParameterParser->getMethod('getPathValue');
        $reflectedGetPathValue->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetPathValue->invokeArgs($parameterParser, [
            $mockRequest,
            [
                'name' => 'id',
                'type' => 'string',
            ],
            '/path/{id}',
        ]);

        $this->assertEquals('1234', $result);
    }

    public function testGetPathValueReturnsExplodedValueIfMatched()
    {
        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')
            ->willReturn('/path/1234,5678');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetPathValue = $reflectedParameterParser->getMethod('getPathValue');
        $reflectedGetPathValue->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetPathValue->invokeArgs($parameterParser, [
            $mockRequest,
            [
                'name' => 'id',
                'type' => 'array',
            ],
            '/path/{id}',
        ]);

        $this->assertEquals([
            '1234',
            '5678',
        ], $result);
    }

    public function testExplodeValue()
    {
        $parameter = [
            'collectionFormat' => 'csv',
        ];

        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedExplodeValue = $reflectedParameterParser->getMethod('explodeValue');
        $reflectedExplodeValue->setAccessible(true);

        $parameterParser = $this->getMockBuilder(ParameterParser::class)
            ->setMethods([ 'getDelimiter' ])
            ->getMock();
        $parameterParser->expects($this->once())
            ->method('getDelimiter')
            ->with($parameter)
            ->willReturn(',');

        $result = $reflectedExplodeValue->invokeArgs(
            $parameterParser,
            [
                'value1,value2',
                $parameter,
            ]
        );

        $this->assertEquals([
            'value1',
            'value2',
        ], $result);
    }

    public function testGetDelimiterHandlesCsv()
    {
        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetDelimiter = $reflectedParameterParser->getMethod('getDelimiter');
        $reflectedGetDelimiter->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetDelimiter->invokeArgs(
            $parameterParser,
            [[ 'collectionFormat' => 'csv' ]]
        );

        $this->assertEquals(',', $result);
    }

    public function testGetDelimiterHandlesSsv()
    {
        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetDelimiter = $reflectedParameterParser->getMethod('getDelimiter');
        $reflectedGetDelimiter->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetDelimiter->invokeArgs(
            $parameterParser,
            [[ 'collectionFormat' => 'ssv' ]]
        );

        $this->assertEquals('\s', $result);
    }

    public function testGetDelimiterHandlesTsv()
    {
        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetDelimiter = $reflectedParameterParser->getMethod('getDelimiter');
        $reflectedGetDelimiter->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetDelimiter->invokeArgs(
            $parameterParser,
            [[ 'collectionFormat' => 'tsv' ]]
        );

        $this->assertEquals('\t', $result);
    }

    public function testGetDelimiterHandlesPipes()
    {
        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetDelimiter = $reflectedParameterParser->getMethod('getDelimiter');
        $reflectedGetDelimiter->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetDelimiter->invokeArgs(
            $parameterParser,
            [[ 'collectionFormat' => 'pipes' ]]
        );

        $this->assertEquals('|', $result);
    }

    public function testGetDelimiterHandlesMulti()
    {
        $this->markTestIncomplete('Still not sure how to handle multi');
    }

    public function testGetDelimiterDefaultsToCsv()
    {
        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetDelimiter = $reflectedParameterParser->getMethod('getDelimiter');
        $reflectedGetDelimiter->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetDelimiter->invokeArgs($parameterParser, [[]]);
    }

    /**
     * @expectedException Exception
     * @expectedException invalid collectionFormat value
     */
    public function testGetDelimiterReturnsCsvForUnknowns()
    {
        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetDelimiter = $reflectedParameterParser->getMethod('getDelimiter');
        $reflectedGetDelimiter->setAccessible(true);

        $parameterParser = new ParameterParser;
        $reflectedGetDelimiter->invokeArgs(
            $parameterParser,
            [[ 'collectionFormat' => 'invalid' ]]
        );
    }
}
