<?php

namespace Zerotoprod\WebFramework;

use InvalidArgumentException;
use RuntimeException;

/**
 * Static facade for route collection and dispatch.
 *
 * Provides convenient static methods for creating route collections
 * and dispatching requests.
 *
 * @link https://github.com/zero-to-prod/web-framework
 */
class Routes
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
     * Create a new route collection.
     *
     * @return self  New route collection instance
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public static function collect(): self
    {
        return new self();
    }

    /**
     * Define a GET route.
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return PendingRoute  Pending route for fluent chaining
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
     * @return self  Returns $this for method chaining
     *
     * @throws InvalidArgumentException  If action is invalid
     */
    public function fallback($action): self
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
     * @param  string  $method   HTTP method
     * @param  string  $uri      Request URI
     * @param  mixed   ...$args  Additional arguments
     *
     * @return bool  True if route or fallback executed
     */
    public function dispatch(string $method, string $uri, ...$args): bool
    {
        $path = $this->stripQueryString($uri);
        $this->buildStaticIndex();

        $key = $method.':'.$path;

        if (isset($this->static_index[$key])) {
            $route = $this->static_index[$key];
            $this->execute($route->action, [], $args);

            return true;
        }

        foreach ($this->routes as $route) {
            if ($route->method !== $method) {
                continue;
            }

            if ($route->matches($path)) {
                $params = $route->extractParams($path);
                $this->execute($route->action, $params, $args);

                return true;
            }
        }

        if ($this->not_found_handler !== null) {
            $this->execute($this->not_found_handler, [], $args);

            return true;
        }

        return false;
    }

    /**
     * Get all registered routes.
     *
     * @return array  Array of Route objects
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
     * @return HttpRoute|null  Matched route or null
     */
    public function matchRoute(string $method, string $uri): ?HttpRoute
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
     */
    public function hasRoute(string $method, string $pattern): bool
    {
        return isset($this->pattern_index[$method.':'.$pattern]);
    }

    /**
     * Check if all routes are cacheable.
     *
     * @return bool  True if all routes can be cached
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
     * @return self  Returns $this for method chaining
     */
    public function loadCompiled(string $data): self
    {
        $routes_data = unserialize($data, ['allowed_classes' => true]);

        if (is_array($routes_data)) {
            foreach ($routes_data as $routeData) {
                $route = HttpRoute::fromArray($routeData);
                $this->storeRoute($route);
            }
        }

        return $this;
    }

    /**
     * Finalize a route by storing it in the collection.
     * Called by PendingRoute when route is complete.
     *
     * @param  HttpRoute  $route  Route to store
     */
    public function finalizeRoute(HttpRoute $route): void
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

        $route = new HttpRoute(
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
     * @param  HttpRoute  $route  Route to store
     */
    private function storeRoute(HttpRoute $route): void
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

    /**
     * Execute an action with parameters.
     *
     * Supports three action types:
     * 1. Controller array: [ClassName::class, 'methodName']
     * 2. Invokable class: ClassName::class (must have __invoke method)
     * 3. Closure: function($params) { ... }
     * 4. String: Plain text to echo
     *
     * @param  mixed  $action  Action to execute
     * @param  array  $params  Route parameters extracted from URI
     * @param  array  $args    Additional arguments to pass to action
     *
     * @throws InvalidArgumentException  If action type is invalid
     */
    public function execute($action, array $params, array $args): void
    {
        if (is_array($action)) {
            $this->executeControllerArray($action, $params, $args);

            return;
        }

        if (is_string($action)) {
            $this->executeString($action, $params, $args);

            return;
        }

        if (!is_callable($action)) {
            throw new InvalidArgumentException('Action must be callable, controller array, or string');
        }

        $action($params, ...$args);
    }

    /**
     * Execute controller array action.
     *
     * @param  array  $action  Controller array [Class, 'method']
     * @param  array  $params  Route parameters
     * @param  array  $args    Additional arguments
     *
     * @throws InvalidArgumentException  If array format is invalid
     */
    private function executeControllerArray(array $action, array $params, array $args): void
    {
        if (!isset($action[0], $action[1]) || isset($action[2])) {
            throw new InvalidArgumentException('Controller array must have exactly 2 elements: [Class, \'method\']');
        }

        (new $action[0]())->{$action[1]}($params, ...$args);
    }

    /**
     * Execute string action.
     *
     * @param  string  $action  String action (invokable class or plain text)
     * @param  array   $params  Route parameters
     * @param  array   $args    Additional arguments
     */
    private function executeString(string $action, array $params, array $args): void
    {
        if (method_exists($action, '__invoke')) {
            (new $action())($params, ...$args);

            return;
        }

        echo $action;
    }

}
