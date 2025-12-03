<?php

namespace Zerotoprod\WebFramework;

use BadMethodCallException;
use InvalidArgumentException;

/**
 * Pending route for fluent constraint chaining.
 *
 * Wraps a Route during fluent method chaining to allow where() and name()
 * calls before the route is finalized and stored in the collection.
 *
 * @link https://github.com/zero-to-prod/web-framework
 */
class PendingRoute
{
    /** @var Routes */
    private $Routes;

    /** @var HttpRoute */
    private $HttpRoute;

    /** @var bool */
    private $finalized = false;

    /**
     * Create a new pending route.
     *
     * @param  Routes     $Routes     The parent collection
     * @param  HttpRoute  $HttpRoute  The route being configured
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function __construct(Routes $Routes, HttpRoute $HttpRoute)
    {
        $this->Routes = $Routes;
        $this->HttpRoute = $HttpRoute;
    }

    /**
     * Add constraint(s) to the route.
     *
     * @param  string|array  $param    Parameter name or array of constraints
     * @param  string|null   $pattern  Regex pattern (if $param is string)
     *
     * @return PendingRoute  Returns $this for method chaining
     *
     * @throws InvalidArgumentException  If constraint is invalid
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function where($param, $pattern = null): PendingRoute
    {
        if (is_array($param)) {
            foreach ($param as $key => $value) {
                $this->validateConstraint($key, $value);
            }
            $this->HttpRoute = $this->HttpRoute->withConstraints($param);
        } else {
            $this->validateConstraint($param, $pattern);
            $this->HttpRoute = $this->HttpRoute->withConstraint($param, $pattern);
        }

        return $this;
    }

    /**
     * Name the route.
     *
     * @param  string  $name  Route name
     *
     * @return PendingRoute  Returns $this for method chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function name(string $name): PendingRoute
    {
        $this->HttpRoute = $this->HttpRoute->withName($name);

        return $this;
    }

    /**
     * Add middleware to the route.
     *
     * @param  mixed  $middleware  Single middleware (callable/class name) or array
     *
     * @return PendingRoute  Returns $this for method chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function middleware($middleware): PendingRoute
    {
        $this->HttpRoute = $this->HttpRoute->withMiddleware($middleware);

        return $this;
    }

    /**
     * Explicitly register the route with the collection.
     *
     * This method provides explicit control over when the route is finalized
     * and stored in the collection. Without calling this method, finalization
     * will occur implicitly when:
     * - Another route method is called (get, post, etc.)
     * - A terminal method is called (dispatch, getRoutes, etc.)
     * - The PendingRoute object is destroyed (via __destruct)
     *
     * @return Routes  The parent collection for further chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function register(): Routes
    {
        $this->finalize();

        return $this->Routes;
    }

    /**
     * Define a GET route (finalizes current route).
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  New pending route
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function get(string $uri, $action = null): PendingRoute
    {
        $this->finalize();

        return $this->Routes->get($uri, $action);
    }

    /**
     * Define a POST route (finalizes current route).
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  New pending route
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function post(string $uri, $action = null): PendingRoute
    {
        $this->finalize();

        return $this->Routes->post($uri, $action);
    }

    /**
     * Define a PUT route (finalizes current route).
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  New pending route
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function put(string $uri, $action = null): PendingRoute
    {
        $this->finalize();

        return $this->Routes->put($uri, $action);
    }

    /**
     * Define a PATCH route (finalizes current route).
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  New pending route
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function patch(string $uri, $action = null): PendingRoute
    {
        $this->finalize();

        return $this->Routes->patch($uri, $action);
    }

    /**
     * Define a DELETE route (finalizes current route).
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  New pending route
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function delete(string $uri, $action = null): PendingRoute
    {
        $this->finalize();

        return $this->Routes->delete($uri, $action);
    }

    /**
     * Define an OPTIONS route (finalizes current route).
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  New pending route
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function options(string $uri, $action = null): PendingRoute
    {
        $this->finalize();

        return $this->Routes->options($uri, $action);
    }

    /**
     * Define a HEAD route (finalizes current route).
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  New pending route
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function head(string $uri, $action = null): PendingRoute
    {
        $this->finalize();

        return $this->Routes->head($uri, $action);
    }

    /**
     * Define fallback handler (finalizes current route).
     *
     * @param  mixed  $action  Fallback action
     *
     * @return Routes  The collection
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function fallback($action): Routes
    {
        $this->finalize();

        return $this->Routes->fallback($action);
    }

    /**
     * Dispatch request (finalizes current route).
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri     Request URI
     * @param  mixed   ...$args Additional arguments
     *
     * @return bool  True if route matched
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function dispatch(string $method, string $uri, ...$args): bool
    {
        $this->finalize();

        return $this->Routes->dispatch($method, $uri, ...$args);
    }

    /**
     * Get all routes (finalizes current route).
     *
     * @return array  Array of Route objects
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function getRoutes(): array
    {
        $this->finalize();

        return $this->Routes->getRoutes();
    }

    /**
     * Match route (finalizes current route).
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri     Request URI
     *
     * @return HttpRoute|null  Matched route or null
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function matchRoute(string $method, string $uri)
    {
        $this->finalize();

        return $this->Routes->matchRoute($method, $uri);
    }

    /**
     * Check if route exists (finalizes current route).
     *
     * @param  string  $method   HTTP method
     * @param  string  $pattern  Route pattern
     *
     * @return bool  True if exists
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function hasRoute(string $method, string $pattern): bool
    {
        $this->finalize();

        return $this->Routes->hasRoute($method, $pattern);
    }

    /**
     * Check if routes are cacheable (finalizes current route).
     *
     * @return bool  True if cacheable
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function isCacheable(): bool
    {
        $this->finalize();

        return $this->Routes->isCacheable();
    }

    /**
     * Compile routes (finalizes current route).
     *
     * @return string  Serialized route data
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function compile(): string
    {
        $this->finalize();

        return $this->Routes->compile();
    }

    /**
     * Finalize the pending route by storing it in the collection.
     */
    private function finalize(): void
    {
        if (!$this->finalized) {
            $this->Routes->finalizeRoute($this->HttpRoute);
            $this->finalized = true;
        }
    }

    /**
     * Auto-finalize on destruction.
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function __destruct()
    {
        $this->finalize();
    }

    /**
     * Validate constraint pattern.
     *
     * @param  string  $param    Parameter name
     * @param  string  $pattern  Regex pattern
     *
     * @throws InvalidArgumentException  If invalid
     */
    private function validateConstraint(string $param, string $pattern): void
    {
        if (@preg_match('#^'.$pattern.'$#', '') === false) {
            throw new InvalidArgumentException("Invalid regex pattern for constraint '{$param}': {$pattern}");
        }
    }
}
