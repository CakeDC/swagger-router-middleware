<?php

namespace AvalancheDevelopment\SwaggerRouter;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use ReflectionClass;

class RouterTest extends PHPUnit_Framework_TestCase
{

    public function testImplementsLoggerAwareInterface()
    {
        $router = new Router([]);

        $this->assertInstanceOf(LoggerAwareInterface::class, $router);
    }

    public function testConstructSetsSwaggerParameter()
    {
        $swagger = [
            'swagger' => '2.0',
        ];

        $router = new Router($swagger);

        $this->assertAttributeSame($swagger, 'swagger', $router);
    }

    public function testConstructSetsNullLogger()
    {
        $logger = new NullLogger;

        $router = new Router([]);

        $this->assertAttributeEquals($logger, 'logger', $router);
    }

    public function testMatchPathPassesMatchedNonVariablePath()
    {
        $testPath = '/test-path';

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')
            ->willReturn('/test-path');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedMatchPath = $reflectedRouter->getMethod('matchPath');
        $reflectedMatchPath->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedMatchPath->invokeArgs($router, [
            $mockRequest,
            $testPath,
        ]);

        $this->assertTrue($result);
    }

    public function testMatchPathFailsUnmatchedNonVariablePath()
    {
        $testPath = '/test-path';

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')
            ->willReturn('/not-test-path');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedMatchPath = $reflectedRouter->getMethod('matchPath');
        $reflectedMatchPath->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedMatchPath->invokeArgs($router, [
            $mockRequest,
            $testPath,
        ]);

        $this->assertFalse($result);
    }

    public function testMatchPathPassesMatchedVariablePath()
    {
        $testPath = '/resource/{resource_id}';

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')
            ->willReturn('/resource/123');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedMatchPath = $reflectedRouter->getMethod('matchPath');
        $reflectedMatchPath->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedMatchPath->invokeArgs($router, [
            $mockRequest,
            $testPath,
        ]);

        $this->assertTrue($result);
    }

    public function testMatchPathFailsUnmatchedVariablePath()
    {
        $testPath = '/resource/{resource_id}';

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')
            ->willReturn('/other-resource/123');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUri);

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedMatchPath = $reflectedRouter->getMethod('matchPath');
        $reflectedMatchPath->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedMatchPath->invokeArgs($router, [
            $mockRequest,
            $testPath,
        ]);

        $this->assertFalse($result);
    }

    public function testHydrateParameterValuesHandlesNoParameters()
    {
        $mockRequest = $this->createMock(RequestInterface::class);

        $mockParser = $this->createMock(ParameterParser::class);
        $mockParser->expects($this->never())
            ->method('__invoke');

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedHydrateParameterValues = $reflectedRouter->getMethod('hydrateParameterValues');
        $reflectedHydrateParameterValues->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedHydrateParameterValues->invokeArgs($router, [
            $mockParser,
            $mockRequest,
            [],
            '',
        ]);
    }

    public function testHydrateParameterValuesHandlesMultipleParameters()
    {
        $route = 'some route';
        $parameters = [
            [ 'name' => 'parameter one' ],
            [ 'name' => 'parameter two' ],
        ];

        $mockRequest = $this->createMock(RequestInterface::class);

        $valueMap = [
            [ $mockRequest, $parameters[0], $route, 'value one' ],
            [ $mockRequest, $parameters[1], $route, 'value two' ],
        ];

        $mockParser = $this->createMock(ParameterParser::class);
        $mockParser->expects($this->exactly(2))
            ->method('__invoke')
            ->withConsecutive(
                [ $mockRequest, $parameters[0], $route ],
                [ $mockRequest, $parameters[1], $route ]
            )
            ->will($this->returnValueMap($valueMap));

        $reflectedRouter = new ReflectionClass(Router::class);
        $reflectedHydrateParameterValues = $reflectedRouter->getMethod('hydrateParameterValues');
        $reflectedHydrateParameterValues->setAccessible(true);

        $router = new Router([]);
        $result = $reflectedHydrateParameterValues->invokeArgs($router, [
            $mockParser,
            $mockRequest,
            $parameters,
            $route,
        ]);

        $this->assertEquals([
            [
                'name' => 'parameter one',
                'value' => 'value one',
            ],
            [
                'name' => 'parameter two',
                'value' => 'value two',
            ],
        ], $result);
    }
}
