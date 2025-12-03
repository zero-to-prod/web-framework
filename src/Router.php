<?php

namespace Zerotoprod\WebFramework;

use Closure;
use InvalidArgumentException;
use RuntimeException;

/**
 * Static facade for route collection and dispatch.
 *
 * Provides convenient static methods for creating route collections
 * and dispatching requests.
 *
 * Performance optimizations:
 * - Method-based indexing for O(1) method filtering
 * - Build indices during registration, not during dispatch
 * - Reduced iterations through route matching
 *
 * @link https://github.com/zero-to-prod/web-framework
 */
class Router
{
    /** @var array */
    private $routes = [];

    /** @var array Indexed by method:path for O(1) static route lookup */
    private $static_index = [];

    /** @var array Indexed by method for quick method filtering */
    private $method_index = [];

    /** @var array Indexed by method:prefix for prefix-based lookup */
    private $prefix_index = [];

    /** @var array */
    private $pattern_index = [];

    /** @var callable|null */
    private $not_found_handler = null;

    /** @var array Global middleware applied to all routes */
    private $global_middleware = [];
    /**
     * @var string
     */
    private $method;
    /**
     * @var string
     */
    private $uri;
    /**
     * @var array
     */
    private $args;

    public function __construct(string $method, string $uri, ...$args)
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->args = $args;
    }

    /**
     * Create a new route collection.
     *
     * @return self  New route collection instance
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public static function for(string $method, string $uri, ...$args): self
    {
        return new self($method, $uri, ...$args);
    }

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
     * @return self  Returns $this for method chaining
     *
     * @throws InvalidArgumentException  If action is invalid
     * @link https://github.com/zero-to-prod/web-framework
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
     * Register global middleware applied to all routes.
     *
     * @param  mixed  $middleware  Single middleware (callable/class name) or array
     *
     * @return self  Returns $this for method chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function middleware($middleware): self
    {
        if (is_array($middleware)) {
            $this->global_middleware = array_merge($this->global_middleware, $middleware);
        } else {
            $this->global_middleware[] = $middleware;
        }

        return $this;
    }

    /**
     * Dispatch a request with triple-level optimization.
     *
     * Performance improvements:
     * - O(1) static route lookup (hash map for exact matches)
     * - O(1) prefix-based filtering (check only routes with matching prefix)
     * - Single regex call per route (matchAndExtract eliminates duplicate preg_match)
     * - O(1) method filtering as fallback
     *
     * @return bool  True if route or fallback executed
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function dispatch(): bool
    {
        $path = $this->stripQueryString($this->uri);
        $key = $this->method.':'.$path;

        // Level 1: O(1) lookup for static routes
        if (isset($this->static_index[$key])) {
            $this->executeWithMiddleware($this->static_index[$key], [], ...$this->args);
            return true;
        }

        // Level 2: Prefix-based lookup (narrow down candidates dramatically)
        $prefix = $this->extractPrefix($path);
        $prefix_key = $this->method.':'.$prefix;

        if (isset($this->prefix_index[$prefix_key])) {
            // Only check routes with matching prefix
            // Use matchAndExtract to avoid double regex call
            foreach ($this->prefix_index[$prefix_key] as $route) {
                $params = [];
                if ($route->matchAndExtract($path, $params)) {
                    $this->executeWithMiddleware($route, $params, ...$this->args);
                    return true;
                }
            }
        }

        // Level 3: Fall back to method-based filtering for routes without specific prefix
        if (isset($this->method_index[$this->method])) {
            foreach ($this->method_index[$this->method] as $route) {
                //  Skip if already checked via prefix
                if ($prefix !== '/' && strpos($route->pattern, $prefix) === 0) {
                    continue;
                }

                $params = [];
                if ($route->matchAndExtract($path, $params)) {
                    $this->executeWithMiddleware($route, $params, ...$this->args);
                    return true;
                }
            }
        }

        if ($this->not_found_handler !== null) {
            $this->executeWithMiddleware(null, [], ...$this->args);
            return true;
        }

        return false;
    }

    /**
     * Get all registered routes.
     *
     * @return array  Array of Route objects
     * @return array  Array of Route objects
     * @internal This method is primarily for testing and debugging.
     *           Production code should use dispatch() instead.
     *
     * @link     https://github.com/zero-to-prod/web-framework
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Find a matching route for method and URI.
     *
     * Uses same triple-level optimization as dispatch().
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri     Request URI
     *
     * @return HttpRoute|null  Matched route or null
     * @internal This method is primarily for testing and debugging.
     *           Production code should use dispatch() instead.
     *
     * @internal This method is primarily for testing and debugging.
     *           Production code should use dispatch() instead.
     *
     * @link     https://github.com/zero-to-prod/web-framework
     */
    public function matchRoute(string $method, string $uri): ?HttpRoute
    {
        $path = $this->stripQueryString($uri);
        $key = $method.':'.$path;

        // Level 1: Static route lookup
        if (isset($this->static_index[$key])) {
            return $this->static_index[$key];
        }

        // Level 2: Prefix-based lookup
        $prefix = $this->extractPrefix($path);
        $prefix_key = $method.':'.$prefix;

        if (isset($this->prefix_index[$prefix_key])) {
            foreach ($this->prefix_index[$prefix_key] as $route) {
                if ($route->matches($path)) {
                    return $route;
                }
            }
        }

        // Level 3: Method-based fallback
        if (isset($this->method_index[$method])) {
            foreach ($this->method_index[$method] as $route) {
                if ($prefix !== '/' && strpos($route->pattern, $prefix) === 0) {
                    continue;
                }

                if ($route->matches($path)) {
                    return $route;
                }
            }
        }

        return null;
    }

    /**
     * Check if a route exists.
     *
     * @param  string  $method   HTTP method
     * @param  string  $pattern  Route pattern
     *
     * @return bool  True if route exists
     * @internal This method is primarily for testing and duplicate detection.
     *           Not typically needed in production code.
     *
     * @link     https://github.com/zero-to-prod/web-framework
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
        return !array_filter($this->global_middleware, function ($mw) {
                return $mw instanceof Closure;
            })
            && !array_filter($this->routes, function ($route) {
                return !$route->isCacheable();
            });
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
                'Use controller arrays [Controller::class, \'method\'], '.
                'invokeable classes Controller::class, or '.
                'middleware class names instead of closures.'
            );
        }

        return serialize([
            'routes' => array_map(static function ($route) {
                return $route->toArray();
            }, $this->routes),
            'global_middleware' => $this->global_middleware
        ]);
    }

    /**
     * Load compiled routes from cache.
     *
     * @param  string  $data  Serialized route data
     *
     * @return self  Returns $this for method chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function loadCompiled(string $data): self
    {
        $compiled = unserialize($data, ['allowed_classes' => true]);

        $routes = isset($compiled[0]) ? $compiled : ($compiled['routes'] ?? []);
        $this->global_middleware = $compiled['global_middleware'] ?? [];

        foreach ($routes as $routeData) {
            $this->storeRoute(HttpRoute::fromArray($routeData));
        }

        return $this;
    }

    /**
     * Finalize a route by storing it in the collection.
     *
     * @param  HttpRoute  $route  Route to store
     *
     * @internal This method is intended for internal use by PendingRoute only.
     *           Do not call directly. Use ->register() for explicit registration.
     *
     * @link     https://github.com/zero-to-prod/web-framework
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
            throw new InvalidArgumentException("Action cannot be null for route: $method $uri");
        }

        $uri = $uri === '' || $uri[0] !== '/'
            ? '/'.$uri
            : $uri;

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
     * Store a route and update all indices immediately.
     *
     * Performance optimization: Build indices during registration
     * rather than lazily during dispatch.
     *
     * @param  HttpRoute  $route  Route to store
     */
    private function storeRoute(HttpRoute $route): void
    {
        $this->routes[] = $route;
        $this->pattern_index[$route->method.':'.$route->pattern] = $route;

        // Build static index for routes without parameters (O(1) lookup)
        if (empty($route->params)) {
            $this->static_index[$route->method.':'.$route->pattern] = $route;
        } else {
            // Build method index for dynamic routes (group by HTTP method)
            if (!isset($this->method_index[$route->method])) {
                $this->method_index[$route->method] = [];
            }
            $this->method_index[$route->method][] = $route;

            // Build prefix index for faster dynamic route matching
            // Extract static prefix (everything before first parameter)
            $prefix = $this->extractPrefix($route->pattern);
            if ($prefix !== '/') {
                $prefix_key = $route->method.':'.$prefix;
                if (!isset($this->prefix_index[$prefix_key])) {
                    $this->prefix_index[$prefix_key] = [];
                }
                $this->prefix_index[$prefix_key][] = $route;
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
     * Extract static prefix from a route pattern or path.
     *
     * Returns everything before the first parameter placeholder (normalized without trailing slash).
     * Examples:
     * - /users/{id}/posts -> /users
     * - /api/v1/{resource} -> /api/v1
     * - /users -> /users
     * - /{id} -> / (root has no prefix)
     *
     * @param  string  $pattern  Route pattern or path
     *
     * @return string  Static prefix (no trailing slash except for root)
     */
    private function extractPrefix(string $pattern): string
    {
        $pos = strpos($pattern, '{');

        if ($pos === false) {
            // No parameters - return path up to last segment
            $last_slash = strrpos($pattern, '/');
            if ($last_slash === false || $last_slash === 0) {
                return '/';
            }

            return substr($pattern, 0, $last_slash);
        }

        if ($pos === 0 || $pos === 1) {
            return '/';
        }

        // Find the last slash before the first parameter
        $prefix = substr($pattern, 0, $pos);
        $last_slash = strrpos($prefix, '/');

        if ($last_slash === false) {
            return '/';
        }

        $result = substr($prefix, 0, $last_slash);

        return $result === '' ? '/' : $result;
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
     * @link https://github.com/zero-to-prod/web-framework
     */
    private function execute($action, array $params, array $args): void
    {
        if (is_array($action)) {
            if (!isset($action[0], $action[1]) || isset($action[2])) {
                throw new InvalidArgumentException('Controller array must have exactly 2 elements: [Class, \'method\']');
            }

            (new $action[0]())->{$action[1]}($params, ...$args);

            return;
        }

        if (is_string($action)) {
            if (method_exists($action, '__invoke')) {
                (new $action())($params, ...$args);

                return;
            }

            echo $action;

            return;
        }

        if (!is_callable($action)) {
            throw new InvalidArgumentException('Action must be callable, controller array, or string');
        }

        $action($params, ...$args);
    }

    /**
     * Execute route action with middleware pipeline.
     *
     * @param  HttpRoute|null  $route   Matched route (null for fallback)
     * @param  array           $params  Route parameters
     * @param  array           $args    Additional dispatch arguments
     *
     * @return void
     * @link https://github.com/zero-to-prod/web-framework
     */
    private function executeWithMiddleware($route, array $params, ...$args): void
    {
        $middleware = $route && $route->middleware
            ? array_merge($this->global_middleware, $route->middleware)
            : $this->global_middleware;

        if (empty($middleware)) {
            $this->execute($route ? $route->action : $this->not_found_handler, $params, $args);

            return;
        }

        $pipeline = function () use ($route, $params, $args) {
            $this->execute($route ? $route->action : $this->not_found_handler, $params, $args);
        };

        foreach (array_reverse($middleware) as $mw) {
            $next = $pipeline;
            $pipeline = function () use ($mw, $next, $args) {
                (is_string($mw) ? new $mw() : $mw)($next, ...$args);
            };
        }

        $pipeline();
    }

}
