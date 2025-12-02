<?php

namespace Zerotoprod\WebFramework;

use Closure;
use InvalidArgumentException;
use RuntimeException;

/**
 * Collection of HTTP routes with fluent registration API.
 *
 * Provides Laravel-style routing with inline constraints, optional parameters,
 * and fluent where() chaining via PendingRoute.
 *
 * @link https://github.com/zero-to-prod/web-framework
 */
class RouteCollection
{
    /** @var array */
    private $routes = [];

    /** @var array|null */
    private $static_index = null;

    /** @var array */
    private $pattern_index = [];

    /** @var callable|null */
    private $not_found_handler = null;

    /**
     * Define a GET route.
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  Pending route for fluent chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function get(string $uri, $action = null): PendingRoute
    {
        return $this->addRoute('GET', $uri, $action);
    }

    /**
     * Define a POST route.
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  Pending route for fluent chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function post(string $uri, $action = null): PendingRoute
    {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Define a PUT route.
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  Pending route for fluent chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function put(string $uri, $action = null): PendingRoute
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Define a PATCH route.
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  Pending route for fluent chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function patch(string $uri, $action = null): PendingRoute
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Define a DELETE route.
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  Pending route for fluent chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function delete(string $uri, $action = null): PendingRoute
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Define an OPTIONS route.
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  Pending route for fluent chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function options(string $uri, $action = null): PendingRoute
    {
        return $this->addRoute('OPTIONS', $uri, $action);
    }

    /**
     * Define a HEAD route.
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  Pending route for fluent chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function head(string $uri, $action = null): PendingRoute
    {
        return $this->addRoute('HEAD', $uri, $action);
    }

    /**
     * Define fallback handler for 404 responses.
     *
     * @param  mixed  $action  Fallback action
     *
     * @return RouteCollection  Returns $this for method chaining
     *
     * @throws InvalidArgumentException  If action is invalid
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function fallback($action): RouteCollection
    {
        if ($action === null) {
            throw new InvalidArgumentException('Fallback action cannot be null');
        }

        $this->not_found_handler = $action;

        return $this;
    }

    /**
     * Dispatch a request.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri     Request URI
     * @param  mixed   ...$args Additional arguments
     *
     * @return bool  True if route or fallback executed
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function dispatch(string $method, string $uri, ...$args): bool
    {
        $path = $this->stripQueryString($uri);
        $this->buildStaticIndex();

        $key = $method.':'.$path;

        if (isset($this->static_index[$key])) {
            $route = $this->static_index[$key];
            ActionExecutor::execute($route->action, [], $args);

            return true;
        }

        foreach ($this->routes as $route) {
            if ($route->method !== $method) {
                continue;
            }

            if ($route->matches($path)) {
                $params = $route->extractParams($path);
                ActionExecutor::execute($route->action, $params, $args);

                return true;
            }
        }

        if ($this->not_found_handler !== null) {
            ActionExecutor::execute($this->not_found_handler, [], $args);

            return true;
        }

        return false;
    }

    /**
     * Get all registered routes.
     *
     * @return array  Array of Route objects
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Find matching route for method and URI.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri     Request URI
     *
     * @return Route|null  Matched route or null
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function matchRoute(string $method, string $uri)
    {
        $path = $this->stripQueryString($uri);
        $this->buildStaticIndex();

        $key = $method.':'.$path;

        if (isset($this->static_index[$key])) {
            return $this->static_index[$key];
        }

        foreach ($this->routes as $route) {
            if ($route->method === $method && $route->matches($path)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Check if route exists.
     *
     * @param  string  $method   HTTP method
     * @param  string  $pattern  Route pattern
     *
     * @return bool  True if route exists
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function hasRoute(string $method, string $pattern): bool
    {
        return isset($this->pattern_index[$method.':'.$pattern]);
    }

    /**
     * Check if all routes are cacheable.
     *
     * @return bool  True if all routes can be cached
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function isCacheable(): bool
    {
        foreach ($this->routes as $route) {
            if (!$route->isCacheable()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compile routes for caching.
     *
     * @return string  Serialized route data
     *
     * @throws RuntimeException  If routes contain closures
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function compile(): string
    {
        if (!$this->isCacheable()) {
            throw new RuntimeException(
                'Cannot compile routes with closures for caching. '.
                'Closures cannot be serialized in PHP. '.
                'Use controller arrays [Controller::class, \'method\'] or '.
                'invokeable classes Controller::class instead.'
            );
        }

        $routes_data = array_map(function ($route) {
            return $route->toArray();
        }, $this->routes);

        return serialize($routes_data);
    }

    /**
     * Load compiled routes from cache.
     *
     * @param  string  $data  Serialized route data
     *
     * @return RouteCollection  Returns $this for method chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function loadCompiled(string $data): RouteCollection
    {
        $routes_data = unserialize($data, ['allowed_classes' => true]);

        if (is_array($routes_data)) {
            foreach ($routes_data as $routeData) {
                $route = Route::fromArray($routeData);
                $this->storeRoute($route);
            }
        }

        return $this;
    }

    /**
     * Finalize a route by storing it in the collection.
     * Called by PendingRoute when route is complete.
     *
     * @param  Route  $route  Route to store
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function finalizeRoute(Route $route): void
    {
        $this->storeRoute($route);
    }

    /**
     * Create and return a pending route.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  Pending route for chaining
     *
     * @throws InvalidArgumentException  If action is invalid
     */
    private function addRoute(string $method, string $uri, $action): PendingRoute
    {
        if ($action === null) {
            throw new InvalidArgumentException("Action cannot be null for route: {$method} {$uri}");
        }

        $compiled = RouteCompiler::compile($uri);

        $route = new Route(
            $method,
            $uri,
            $compiled['regex'],
            $compiled['params'],
            $compiled['optional_params'],
            $compiled['constraints'],
            $action
        );

        return new PendingRoute($this, $route);
    }

    /**
     * Store a route and update indices.
     *
     * @param  Route  $route  Route to store
     */
    private function storeRoute(Route $route): void
    {
        $this->routes[] = $route;
        $this->pattern_index[$route->method.':'.$route->pattern] = $route;
        $this->static_index = null;
    }

    /**
     * Build static route index for O(1) lookups.
     */
    private function buildStaticIndex(): void
    {
        if ($this->static_index !== null) {
            return;
        }

        $this->static_index = [];

        foreach ($this->routes as $route) {
            if (empty($route->params)) {
                $key = $route->method.':'.$route->pattern;
                $this->static_index[$key] = $route;
            }
        }
    }

    /**
     * Strip query string from URI.
     *
     * @param  string  $uri  Request URI
     *
     * @return string  Path without query string
     */
    private function stripQueryString(string $uri): string
    {
        $path = strtok($uri, '?');

        return $path !== false ? $path : '';
    }
}
