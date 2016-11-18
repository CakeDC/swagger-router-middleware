<?php

namespace AvalancheDevelopment\SwaggerRouterMiddleware\Parser;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use ReflectionClass;

class BodyTest extends PHPUnit_Framework_TestCase
{

    public function testImplementsParserInterface()
    {
        $mockRequest = $this->createMock(RequestInterface::class);

        $bodyParser = new Body($mockRequest);

        $this->isInstanceOf(ParserInterface::class, $bodyParser);
    }

    public function testConstructSetsRequest()
    {
        $mockRequest = $this->createMock(RequestInterface::class);

        $bodyParser = new Body($mockRequest);

        $this->assertAttributeSame($mockRequest, 'request', $bodyParser);
    }

    public function testGetValueReturnsString()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getBody')
            ->willReturn(123);

        $reflectedBodyParser = new ReflectionClass(Body::class);
        $reflectedRequest = $reflectedBodyParser->getProperty('request');
        $reflectedRequest->setAccessible(true);

        $bodyParser = $this->getMockBuilder(Body::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $reflectedRequest->setValue($bodyParser, $mockRequest);

        $result = $bodyParser->getValue();

        $this->assertSame('123', $result);
    }
}
