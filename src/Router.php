<?php

/**
 * Middleware router based on swagger definition
 * Accepts an incoming request object and passes on a decorated request object
 * Request object will have attributes filled out w/ route information
 * Also, reserved 'swagger' attribute with the operation info from swagger
 * Throws exception for unmatched request
 */

namespace AvalancheDevelopment\SwaggerRouterMiddleware;

use Psr\Http\Message\Request as Request;
use Psr\Http\Message\ServerRequest as Response;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Router implements LoggerAwareInterface
{

    use LoggerAwareTrait;

    /** @var array $swagger */
    protected $swagger;

    /**
     * @param array $swagger
     */
    public function __construct(array $swagger)
    {
        $this->swagger = $swagger;
        $this->logger = new NullLogger;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return ServerRequest
     */
    public function __invoke(Request $request, Response $response, callable $next)
    {
        $matchedPath = false;
        foreach ($this->swagger['paths'] as $route => $pathItem) {
            if ($this->matchPath($request, $route)) {
                $matchedPath = true;
                break;
            }
        }
        if (!$matchedPath) {
            $response = $response->withStatus(404);
            return $response;
        }

        $method = strtolower($request->getMethod());
        if (!array_key_exists($method, $pathItem)) {
            $response = $response->withStatus(405);
            return $response;
        }

        $operation = $pathItem[$method];

        $parameters = $this->getParameters($pathItem, $operation);

        $parser = new ParameterParser;
        $parameters = $this->hydrateParameterValues($parser, $request, $parameters, $route);

        // todo security would be cool here too

        $request = $request->withAttribute('swagger', [
            'path' => $pathItem,
            'operation' => $operation,
            'params' => $parameters,
        ]);
        return $next($request, $response);
    }

    /**
     * @param Request $request
     * @param string $route
     * @response boolean
     */
    protected function matchPath(Request $request, $route)
    {
        $isVariablePath = strstr($route, '{') && strstr($route, '}');
        if (!$isVariablePath && $request->getUri()->getPath() === $route) {
            return true;
        }

        $variablePath = preg_replace('/({[a-z_]+})/', '\w+', $route);
        $variablePath = "@^{$variablePath}$@";
        $matchedVariablePath = preg_match($variablePath, $request->getUri()->getPath());
        if ($matchedVariablePath) {
            return true;
        }

        return false;
    }

    /**
     * @param array $pathItem
     * @param array $operation
     * @return array
     */
    protected function getParameters(array $pathItem, array $operation)
    {
        $uniqueParameters = [];
        if (array_key_exists('parameters', $pathItem)) {
            foreach ($pathItem['parameters'] as $parameter) {
                $key = $this->uniqueParameterKey($parameter);
                $uniqueParameters[$key] = $parameter;
            }
        }
        if (array_key_exists('parameters', $operation)) {
            foreach ($operation['parameters'] as $parameter) {
                $key = $this->uniqueParameterKey($parameter);
                $uniqueParameters[$key] = $parameter;
            }
        }

        return array_values($uniqueParameters);
    }

    /**
     * @param array $parameter
     * @return string
     */
    protected function uniqueParameterKey(array $parameter)
    {
        return "{$parameter['name']}-{$parameter['in']}";
    }

    /**
     * @param ParameterParser $parser
     * @param Request $request
     * @param array $parameters
     * @param string $route
     * @return array
     */
    protected function hydrateParameterValues(
        ParameterParser $parser,
        Request $request,
        array $parameters,
        $route
    ) {
        return array_map(function ($parameter) use ($parser, $request, $route) {
            $parameter['value'] = $parser($request, $parameter, $route);
            return $parameter;
        }, $parameters);
    }
}
