<?php

namespace AvalancheDevelopment\SwaggerRouter;

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

    public function testGetDelimiterReturnsCsvForUnknowns()
    {
        $reflectedParameterParser = new ReflectionClass(ParameterParser::class);
        $reflectedGetDelimiter = $reflectedParameterParser->getMethod('getDelimiter');
        $reflectedGetDelimiter->setAccessible(true);

        $parameterParser = new ParameterParser;
        $result = $reflectedGetDelimiter->invokeArgs(
            $parameterParser,
            [[ 'collectionFormat' => 'invalid' ]]
        );

        $this->assertEquals(',', $result);
    }
}
