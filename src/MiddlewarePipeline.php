<?php

namespace Zerotoprod\WebFramework;

use Closure;
use InvalidArgumentException;

/**
 * Executes a middleware stack using the pipeline pattern.
 *
 * Builds a chain of callables that pass control to the next middleware
 * via the $next() callable, ultimately executing the final action.
 *
 * @link https://github.com/zero-to-prod/web-framework
 */
class MiddlewarePipeline
{
    /** @var array */
    private $middleware;

    /**
     * @param array $middleware Array of middleware callables/class names
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function __construct(array $middleware)
    {
        $this->middleware = $middleware;
    }

    /**
     * Execute the middleware pipeline with the final action.
     *
     * @param callable $final_action The final action to execute
     *
     * @return void
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function execute(callable $final_action): void
    {
        $pipeline = $this->buildPipeline($final_action);
        $pipeline();
    }

    /**
     * Build the pipeline as a chain of nested closures.
     *
     * Works backwards through middleware array to build the chain:
     * - Last middleware wraps final action
     * - Each previous middleware wraps the next
     * - Returns outermost middleware closure
     *
     * @param callable $final_action The final action to execute
     *
     * @return callable The pipeline entry point
     */
    private function buildPipeline(callable $final_action): callable
    {
        $pipeline = $final_action;

        foreach (array_reverse($this->middleware) as $middleware) {
            $pipeline = $this->createMiddlewareLayer($middleware, $pipeline);
        }

        return $pipeline;
    }

    /**
     * Create a middleware layer that wraps the next callable.
     *
     * @param mixed $middleware Middleware callable or class name
     * @param callable $next Next layer in the pipeline
     *
     * @return callable
     */
    private function createMiddlewareLayer($middleware, callable $next): callable
    {
        return function () use ($middleware, $next) {
            $callable = $this->resolveMiddleware($middleware);

            $callable($_SERVER, $next);
        };
    }

    /**
     * Resolve middleware to a callable.
     *
     * @param mixed $middleware Middleware callable or class name
     *
     * @return callable
     * @throws InvalidArgumentException If middleware cannot be resolved
     */
    private function resolveMiddleware($middleware): callable
    {
        if ($middleware instanceof Closure) {
            return $middleware;
        }

        if (is_string($middleware) && class_exists($middleware)) {
            $instance = new $middleware();
            if (method_exists($instance, '__invoke')) {
                return $instance;
            }
            throw new InvalidArgumentException(
                "Middleware class {$middleware} must have an __invoke method"
            );
        }

        if (is_callable($middleware)) {
            return $middleware;
        }

        throw new InvalidArgumentException(
            'Middleware must be a callable, closure, or invokable class name'
        );
    }
}
