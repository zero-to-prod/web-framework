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
 * Usage example:
 * <code>
 * $router = Router::for('GET', '/users/123')
 *     ->get('/users/{id}', [UserController::class, 'show'])
 *     ->where('id', '\d+')
 *     ->name('user.show')
 *     ->middleware(AuthMiddleware::class);
 *
 * $router->post('/users', [UserController::class, 'store'])
 *     ->middleware([ValidationMiddleware::class, AuthMiddleware::class]);
 *
 * $router->fallback(function() {
 *     http_response_code(404);
 *     echo 'Not Found';
 * });
 *
 * $router->dispatch();
 * </code>
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

    /** @var Route|null Track last route for fluent chaining */
    private $last_route = null;

    /** @var string HTTP method for dispatch */
    private $method;

    /** @var string Request URI for dispatch */
    private $uri;

    /** @var array Additional arguments passed to actions/middleware */
    private $args = [];

    /** @var bool Whether indices have been built */
    private $indices_built = false;

    /** @var string Error message for methods that require a route to be defined first */
    private const NO_ROUTE_DEFINED = 'No route to configure. Call get/post/etc first.';

    /**
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function __construct()
    {
    }

    /**
     * Create a new router instance.
     *
     * @return self  New router instance
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Create a new router for the given request.
     *
     * Alias for create()->forRequest() for backward compatibility.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri     Request URI
     * @param  mixed   ...$args Additional arguments passed to actions/middleware
     *
     * @return self  New router instance
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public static function for(string $method = '', string $uri = '', ...$args): self
    {
        return self::create()->forRequest($method, $uri, ...$args);
    }

    /**
     * Configure the request to dispatch.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri     Request URI
     * @param  mixed   ...$args Additional arguments passed to actions/middleware
     *
     * @return self  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function forRequest(string $method, string $uri, ...$args): self
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->args = $args;
        return $this;
    }

    /**
     * Define a GET route.
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return self  Returns $this for method chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function get(string $uri, $action = null): self
    {
        $this->addRoute('GET', $uri, $action);
        return $this;
    }

    /**
     * Define a POST route.
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return self  Returns $this for method chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function post(string $uri, $action = null): self
    {
        $this->addRoute('POST', $uri, $action);
        return $this;
    }

    /**
     * Define a PUT route.
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return self  Returns $this for method chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function put(string $uri, $action = null): self
    {
        $this->addRoute('PUT', $uri, $action);
        return $this;
    }

    /**
     * Define a PATCH route.
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return self  Returns $this for method chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function patch(string $uri, $action = null): self
    {
        $this->addRoute('PATCH', $uri, $action);
        return $this;
    }

    /**
     * Define a DELETE route.
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return self  Returns $this for method chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function delete(string $uri, $action = null): self
    {
        $this->addRoute('DELETE', $uri, $action);
        return $this;
    }

    /**
     * Define an OPTIONS route.
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return self  Returns $this for method chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function options(string $uri, $action = null): self
    {
        $this->addRoute('OPTIONS', $uri, $action);
        return $this;
    }

    /**
     * Define a HEAD route.
     *
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return self  Returns $this for method chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function head(string $uri, $action = null): self
    {
        $this->addRoute('HEAD', $uri, $action);
        return $this;
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
     * Register global middleware for all routes.
     *
     * @param  mixed  $middleware  Single middleware (callable/class name) or array
     *
     * @return self  Returns $this for method chaining
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function globalMiddleware($middleware): self
    {
        if (is_array($middleware)) {
            $this->global_middleware = array_merge($this->global_middleware, $middleware);
        } else {
            $this->global_middleware[] = $middleware;
        }

        return $this;
    }

    /**
     * Add middleware to the last defined route.
     *
     * @param  mixed  $middleware  Single middleware (callable/class name) or array
     *
     * @return self  Returns $this for method chaining
     *
     * @throws RuntimeException  If no route has been defined yet
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function middleware($middleware): self
    {
        if ($this->last_route === null) {
            throw new RuntimeException(self::NO_ROUTE_DEFINED.' Use globalMiddleware() for global middleware.');
        }

        $this->last_route = $this->last_route->withMiddleware($middleware);
        $this->replaceLastRoute($this->last_route);

        return $this;
    }

    /**
     * Dispatch a request with triple-level optimization.
     *
     * Executes the matched route action via side effects (echo, output buffering, etc.).
     * Actions do not return values; they produce output directly. The return value
     * indicates whether a route was matched and executed.
     *
     * Performance improvements:
     * - O(1) static route lookup (hash map for exact matches)
     * - O(1) prefix-based filtering (check only routes with matching prefix)
     * - Single regex call per route (matchAndExtract eliminates duplicate preg_match)
     * - O(1) method filtering as fallback
     *
     * @return bool  True if route or fallback executed, false if no match
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function dispatch(): bool
    {
        // Build indices lazily on first dispatch
        if (!$this->indices_built) {
            $this->buildIndices();
        }

        $path = $this->stripQueryString($this->uri);
        $key = $this->method.':'.$path;

        // Level 1: O(1) lookup for static routes
        if (isset($this->static_index[$key])) {
            $this->executeWithMiddleware($this->static_index[$key], []);
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
                    $this->executeWithMiddleware($route, $params);
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
                    $this->executeWithMiddleware($route, $params);
                    return true;
                }
            }
        }

        if ($this->not_found_handler !== null) {
            $this->executeWithMiddleware(null, []);
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
     * @return Route|null  Matched route or null
     * @internal This method is primarily for testing and debugging.
     *           Production code should use dispatch() instead.
     *
     * @link     https://github.com/zero-to-prod/web-framework
     */
    public function matchRoute(string $method, string $uri): ?Route
    {
        // Build indices lazily if not built yet
        if (!$this->indices_built) {
            $this->buildIndices();
        }

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
        return !array_filter($this->global_middleware, static function ($mw) {
                return $mw instanceof Closure;
            })
            && !array_filter($this->routes, static function ($route) {
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
            $this->storeRoute(Route::fromArray($routeData));
        }

        return $this;
    }

    /**
     * Add constraint(s) to the last defined route.
     *
     * @param  string|array  $param    Parameter name or array of constraints
     * @param  string|null   $pattern  Regex pattern (if $param is string)
     *
     * @return self  Returns $this for method chaining
     *
     * @throws RuntimeException  If no route has been defined yet
     * @throws InvalidArgumentException  If constraint is invalid
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function where($param, ?string $pattern = null): self
    {
        if ($this->last_route === null) {
            throw new RuntimeException(self::NO_ROUTE_DEFINED);
        }

        // Validate constraints
        if (is_array($param)) {
            foreach ($param as $key => $value) {
                $this->validateConstraint($key, $value);
            }
        } else {
            $this->validateConstraint($param, $pattern);
        }

        $this->last_route = $this->last_route->where($param, $pattern);
        $this->replaceLastRoute($this->last_route);

        return $this;
    }

    /**
     * Validate constraint pattern.
     *
     * @param  string  $param    Parameter name
     * @param  string  $pattern  Regex pattern
     *
     * @throws InvalidArgumentException  If invalid
     * @internal
     */
    private function validateConstraint(string $param, string $pattern): void
    {
        if (@preg_match('#^'.$pattern.'$#', '') === false) {
            throw new InvalidArgumentException("Invalid regex pattern for constraint '$param': $pattern");
        }
    }

    /**
     * Name the last defined route.
     *
     * @param  string  $name  Route name
     *
     * @return self  Returns $this for method chaining
     *
     * @throws RuntimeException  If no route has been defined yet
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function name(string $name): self
    {
        if ($this->last_route === null) {
            throw new RuntimeException(self::NO_ROUTE_DEFINED);
        }

        $this->last_route = $this->last_route->withName($name);
        $this->replaceLastRoute($this->last_route);

        return $this;
    }

    /**
     * Create and store a route.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return void
     *
     * @throws InvalidArgumentException  If action is invalid
     * @internal
     */
    private function addRoute(string $method, string $uri, $action): void
    {
        if ($action === null) {
            throw new InvalidArgumentException("Action cannot be null for route: $method $uri");
        }

        $uri = $uri === '' || $uri[0] !== '/'
            ? '/'.$uri
            : $uri;

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

        $this->storeRoute($route);
        $this->last_route = $route;
    }

    /**
     * Store a route.
     *
     * Indices are built lazily before first dispatch for better performance
     * when defining many routes.
     *
     * @param  Route  $route  Route to store
     * @internal
     */
    private function storeRoute(Route $route): void
    {
        $this->routes[] = $route;
        $this->pattern_index[$route->method.':'.$route->pattern] = $route;
    }

    /**
     * Build all indices from stored routes.
     *
     * Performance optimization: Build indices once before dispatch
     * rather than updating them after each route modification.
     *
     * @return void
     * @internal
     */
    private function buildIndices(): void
    {
        // Clear existing indices
        $this->static_index = [];
        $this->method_index = [];
        $this->prefix_index = [];

        // Build indices from routes array
        foreach ($this->routes as $route) {
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

        $this->indices_built = true;
    }

    /**
     * Replace the last stored route with an updated version.
     *
     * Performance optimization: This method only updates the routes array and pattern
     * index immediately. The static_index, method_index, and prefix_index are marked
     * as stale (indices_built = false) and will be rebuilt lazily on the next dispatch()
     * call. This approach avoids expensive index rebuilding during route definition when
     * chaining multiple configuration methods like where(), name(), and middleware().
     *
     * @param  Route  $route  Updated route to replace
     * @internal
     */
    private function replaceLastRoute(Route $route): void
    {
        // Replace in routes array
        foreach ($this->routes as $i => $r) {
            if ($r->method === $route->method && $r->pattern === $route->pattern) {
                $this->routes[$i] = $route;
                break;
            }
        }

        // Update pattern index
        $this->pattern_index[$route->method.':'.$route->pattern] = $route;

        // Mark indices as needing rebuild
        $this->indices_built = false;
    }

    /**
     * Strip query string from URI.
     *
     * @param  string  $uri  Request URI
     *
     * @return string  Path without query string
     * @internal
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
     * @internal
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
     *
     * @param  mixed  $action  Action to execute
     * @param  array  $params  Route parameters extracted from URI
     *
     * @throws InvalidArgumentException  If action type is invalid
     * @internal
     * @link https://github.com/zero-to-prod/web-framework
     */
    private function execute($action, array $params): void
    {
        if (is_array($action)) {
            if (!isset($action[0], $action[1]) || isset($action[2])) {
                throw new InvalidArgumentException('Controller array must have exactly 2 elements: [Class, \'method\']');
            }

            (new $action[0]())->{$action[1]}($params, ...$this->args);

            return;
        }

        if (is_string($action) && method_exists($action, '__invoke')) {
            (new $action())($params, ...$this->args);

            return;
        }

        if (!is_callable($action)) {
            throw new InvalidArgumentException('Action must be callable (closure), controller array [Class::class, \'method\'], or invokable class');
        }

        $action($params, ...$this->args);
    }

    /**
     * Execute route action with middleware pipeline.
     *
     * @param  Route|null  $route   Matched route (null for fallback)
     * @param  array       $params  Route parameters
     *
     * @return void
     * @internal
     * @link https://github.com/zero-to-prod/web-framework
     */
    private function executeWithMiddleware(?Route $route, array $params): void
    {
        $middleware = $route && $route->middleware
            ? array_merge($this->global_middleware, $route->middleware)
            : $this->global_middleware;

        if (empty($middleware)) {
            $this->execute($route->action ?? $this->not_found_handler, $params);

            return;
        }

        $pipeline = function () use ($route, $params) {
            $this->execute($route->action ?? $this->not_found_handler, $params);
        };

        foreach (array_reverse($middleware) as $mw) {
            $next = $pipeline;
            $pipeline = function () use ($mw, $next) {
                (is_string($mw) ? new $mw() : $mw)($next, ...$this->args);
            };
        }

        $pipeline();
    }

}
