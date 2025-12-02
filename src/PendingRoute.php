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
    /** @var RouteCollection */
    private $collection;

    /** @var Route */
    private $route;

    /** @var bool */
    private $finalized = false;

    /**
     * Create a new pending route.
     *
     * @param  RouteCollection  $collection  The parent collection
     * @param  Route            $route       The route being configured
     */
    public function __construct(RouteCollection $collection, Route $route)
    {
        $this->collection = $collection;
        $this->route = $route;
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
     */
    public function where($param, $pattern = null): PendingRoute
    {
        if (is_array($param)) {
            foreach ($param as $key => $value) {
                $this->validateConstraint($key, $value);
            }
            $this->route = $this->route->withConstraints($param);
        } else {
            $this->validateConstraint($param, $pattern);
            $this->route = $this->route->withConstraint($param, $pattern);
        }

        return $this;
    }

    /**
     * Name the route.
     *
     * @param  string  $name  Route name
     *
     * @return PendingRoute  Returns $this for method chaining
     */
    public function name(string $name): PendingRoute
    {
        $this->route = $this->route->withName($name);

        return $this;
    }

    /**
     * Define a GET route (finalizes current route).
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  New pending route
     */
    public function get(string $uri, $action = null): PendingRoute
    {
        $this->finalize();

        return $this->collection->get($uri, $action);
    }

    /**
     * Define a POST route (finalizes current route).
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  New pending route
     */
    public function post(string $uri, $action = null): PendingRoute
    {
        $this->finalize();

        return $this->collection->post($uri, $action);
    }

    /**
     * Define a PUT route (finalizes current route).
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  New pending route
     */
    public function put(string $uri, $action = null): PendingRoute
    {
        $this->finalize();

        return $this->collection->put($uri, $action);
    }

    /**
     * Define a PATCH route (finalizes current route).
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  New pending route
     */
    public function patch(string $uri, $action = null): PendingRoute
    {
        $this->finalize();

        return $this->collection->patch($uri, $action);
    }

    /**
     * Define a DELETE route (finalizes current route).
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  New pending route
     */
    public function delete(string $uri, $action = null): PendingRoute
    {
        $this->finalize();

        return $this->collection->delete($uri, $action);
    }

    /**
     * Define an OPTIONS route (finalizes current route).
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  New pending route
     */
    public function options(string $uri, $action = null): PendingRoute
    {
        $this->finalize();

        return $this->collection->options($uri, $action);
    }

    /**
     * Define a HEAD route (finalizes current route).
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  New pending route
     */
    public function head(string $uri, $action = null): PendingRoute
    {
        $this->finalize();

        return $this->collection->head($uri, $action);
    }

    /**
     * Define fallback handler (finalizes current route).
     *
     * @param  mixed  $action  Fallback action
     *
     * @return RouteCollection  The collection
     */
    public function fallback($action): RouteCollection
    {
        $this->finalize();

        return $this->collection->fallback($action);
    }

    /**
     * Dispatch request (finalizes current route).
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri     Request URI
     * @param  mixed   ...$args Additional arguments
     *
     * @return bool  True if route matched
     */
    public function dispatch(string $method, string $uri, ...$args): bool
    {
        $this->finalize();

        return $this->collection->dispatch($method, $uri, ...$args);
    }

    /**
     * Get all routes (finalizes current route).
     *
     * @return array  Array of Route objects
     */
    public function getRoutes(): array
    {
        $this->finalize();

        return $this->collection->getRoutes();
    }

    /**
     * Match route (finalizes current route).
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri     Request URI
     *
     * @return Route|null  Matched route or null
     */
    public function matchRoute(string $method, string $uri)
    {
        $this->finalize();

        return $this->collection->matchRoute($method, $uri);
    }

    /**
     * Check if route exists (finalizes current route).
     *
     * @param  string  $method   HTTP method
     * @param  string  $pattern  Route pattern
     *
     * @return bool  True if exists
     */
    public function hasRoute(string $method, string $pattern): bool
    {
        $this->finalize();

        return $this->collection->hasRoute($method, $pattern);
    }

    /**
     * Check if routes are cacheable (finalizes current route).
     *
     * @return bool  True if cacheable
     */
    public function isCacheable(): bool
    {
        $this->finalize();

        return $this->collection->isCacheable();
    }

    /**
     * Compile routes (finalizes current route).
     *
     * @return string  Serialized route data
     */
    public function compile(): string
    {
        $this->finalize();

        return $this->collection->compile();
    }

    /**
     * Finalize the pending route by storing it in the collection.
     */
    private function finalize(): void
    {
        if (!$this->finalized) {
            $this->collection->finalizeRoute($this->route);
            $this->finalized = true;
        }
    }

    /**
     * Auto-finalize on destruction.
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
