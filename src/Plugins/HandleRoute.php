<?php

namespace Zerotoprod\WebFramework\Plugins;

use Closure;
use RuntimeException;

/**
 * High-performance HTTP routing with O(1) constant-time route matching.
 *
 * @link https://github.com/zero-to-prod/web-framework
 */
class HandleRoute
{
    /**
     * Compiled routes data structure containing both static and dynamic routes.
     *
     * Structure:
     * - 'static': Hash map for O(1) lookups: "METHOD:PATH" => callable
     * - 'dynamic': Array of dynamic route configs: [['method' => 'GET', 'pattern' => '/users/{id}', 'regex' => '...', 'params' => [...], 'action' => callable], ...]
     * - 'regex': Array of user-provided regex route configs: [['method' => 'GET', 'pattern' => '/users/(\d+)/', 'regex' => '...', 'params' => [...], 'action' => callable], ...]
     *
     * @var array
     */
    private $routes = [
        'static' => [],
        'dynamic' => [],
        'regex' => []
    ];

    /**
     * HTTP method for the current request.
     *
     * @var string
     */
    private $request_method;

    /**
     * Request path (query string stripped).
     *
     * @var string
     */
    private $request_path;

    /**
     * Fallback handler for 404 (not found) responses.
     *
     * @var callable|null
     */
    private $not_found_handler = null;

    /**
     * Additional arguments to pass to the action.
     *
     * @var array
     */
    private $args;

    /**
     * Create a new HandleRoute instance.
     *
     * @param  string  $method   HTTP method (GET, POST, etc.)
     * @param  string  $uri      Request URI (query string will be stripped)
     * @param  mixed   ...$args  Additional arguments to pass to the action
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function __construct(string $method, string $uri, ...$args)
    {
        $this->args = $args;
        $this->request_method = $method;

        $path = strtok($uri, '?');
        $this->request_path = $path !== false ? $path : '';
    }

    /**
     * Add a route to the route map.
     *
     * Automatically detects static vs dynamic vs regex routes:
     * - Static routes (no {params}): Stored in hash map for O(1) lookups
     * - Dynamic routes (with {params}): Compiled to regex and stored separately
     * - Regex routes (array format): User-provided regex patterns with explicit params
     *
     * @param  string                      $method  HTTP method (GET, POST, etc.)
     * @param  string|array                $uri     URI pattern to match (e.g., "/users/{id}" or ['/pattern/', ['params']])
     * @param  callable|array|string|null  $action  Action to execute
     *
     * @return HandleRoute  Returns $this for method chaining during definition
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function add(string $method, $uri, $action = null): HandleRoute
    {
        $normalized = $this->normalizeAction($action);

        if ($normalized === null) {
            return $this;
        }

        if ($this->isRegexRouteArray($uri)) {
            $pattern = $uri[0];
            $params = $uri[1] ?? [];

            $compiled = $this->compileRegexRoute($pattern, $params);

            if ($compiled !== null) {
                $this->routes['regex'][] = [
                    'method' => $method,
                    'pattern' => $compiled['pattern'],
                    'regex' => $compiled['regex'],
                    'params' => $compiled['params'],
                    'action' => $normalized
                ];
            }

            return $this;
        }

        if (is_string($uri) && strpos($uri, '{') !== false) {
            $compiled = $this->compilePattern($uri);
            $this->routes['dynamic'][] = [
                'method' => $method,
                'pattern' => $uri,
                'regex' => $compiled['regex'],
                'params' => $compiled['params'],
                'optional_params' => $compiled['optional_params'],
                'action' => $normalized
            ];
        } else {
            $this->routes['static'][$method.':'.$uri] = $normalized;
        }

        return $this;
    }

    /**
     * Define a GET route.
     *
     * @param  string|array                $uri     The URI pattern to match (string or regex array)
     * @param  callable|array|string|null  $action  The action to execute
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function get($uri, $action = null): HandleRoute
    {
        return $this->add('GET', $uri, $action);
    }

    /**
     * Define a POST route.
     *
     * @param  string|array                $uri     The URI pattern to match (string or regex array)
     * @param  callable|array|string|null  $action  The action to execute
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function post($uri, $action = null): HandleRoute
    {
        return $this->add('POST', $uri, $action);
    }

    /**
     * Define a PUT route.
     *
     * @param  string|array                $uri     The URI pattern to match (string or regex array)
     * @param  callable|array|string|null  $action  The action to execute
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function put($uri, $action = null): HandleRoute
    {
        return $this->add('PUT', $uri, $action);
    }

    /**
     * Define a PATCH route.
     *
     * @param  string|array                $uri     The URI pattern to match (string or regex array)
     * @param  callable|array|string|null  $action  The action to execute
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function patch($uri, $action = null): HandleRoute
    {
        return $this->add('PATCH', $uri, $action);
    }

    /**
     * Define a DELETE route.
     *
     * @param  string|array                $uri     The URI pattern to match (string or regex array)
     * @param  callable|array|string|null  $action  The action to execute
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function delete($uri, $action = null): HandleRoute
    {
        return $this->add('DELETE', $uri, $action);
    }

    /**
     * Define an OPTIONS route.
     *
     * @param  string|array                $uri     The URI pattern to match (string or regex array)
     * @param  callable|array|string|null  $action  The action to execute
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function options($uri, $action = null): HandleRoute
    {
        return $this->add('OPTIONS', $uri, $action);
    }

    /**
     * Define a HEAD route.
     *
     * @param  string|array                $uri     The URI pattern to match (string or regex array)
     * @param  callable|array|string|null  $action  The action to execute
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function head($uri, $action = null): HandleRoute
    {
        return $this->add('HEAD', $uri, $action);
    }

    /**
     * Define a fallback handler for 404 (not found) responses.
     *
     * This handler is executed when no route matches the request.
     * The handler receives the server array as a parameter.
     *
     * @param  callable|array|string|null  $action  The action to execute for 404 responses
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function fallback($action = null): HandleRoute
    {
        $this->not_found_handler = $this->normalizeAction($action);

        return $this;
    }

    /**
     * Compile a dynamic route pattern to regex and extract parameter names.
     *
     * Converts patterns like "/users/{id}/posts/{slug}" to regex
     * and extracts parameter names ['id', 'slug'].
     * Supports optional parameters with {param?} syntax.
     *
     * @param  string  $pattern  Route pattern with {param} or {param?} placeholders
     *
     * @return array  ['regex' => compiled regex, 'params' => parameter names, 'optional_params' => optional parameter names]
     */
    private function compilePattern(string $pattern): array
    {
        preg_match_all('/\{([a-zA-Z_]+\w*)\??}/', $pattern, $matches);

        $params = [];
        $optional_params = [];
        $search = [];
        $replace = [];

        foreach ($matches[0] as $index => $placeholder) {
            $param_name = $matches[1][$index];
            $params[] = $param_name;

            if (substr($placeholder, -2, 1) === '?') {
                $optional_params[] = $param_name;
                $search[] = '/'.$placeholder;
                $replace[] = '(?:/([^/]+))?';
            } else {
                $search[] = $placeholder;
                $replace[] = '([^/]+)';
            }
        }

        return [
            'regex' => '#^'.str_replace($search, $replace, $pattern).'$#',
            'params' => $params,
            'optional_params' => $optional_params
        ];
    }

    /**
     * Check if the URI is a valid regex route array format.
     *
     * Valid formats:
     * - Single element: ['/pattern/']
     * - Two elements: ['/pattern/', ['param1', 'param2']]
     *
     * @param  mixed  $uri  The URI to check
     *
     * @return bool  True if valid regex route array format
     */
    private function isRegexRouteArray($uri): bool
    {
        if (!is_array($uri)) {
            return false;
        }

        $count = count($uri);

        if ($count === 1) {
            return isset($uri[0]) && is_string($uri[0]);
        }

        if ($count === 2) {
            return isset($uri[0], $uri[1]) && is_string($uri[0]) && is_array($uri[1]);
        }

        return false;
    }

    /**
     * Compile a regex route pattern and validate it.
     *
     * Validates the regex pattern and optionally checks that the number of
     * capture groups matches the number of parameter names provided.
     *
     * @param  string  $pattern  The regex pattern
     * @param  array   $params   Parameter names to map to capture groups
     *
     * @return array|null  ['pattern' => original, 'regex' => compiled, 'params' => params] or null if invalid
     */
    private function compileRegexRoute(string $pattern, array $params = []): ?array
    {
        if (empty($pattern)) {
            return null;
        }

        $compiled = $pattern;

        if ($pattern[0] === '/' && substr($pattern, -1) === '/') {
            $compiled = '#^'.substr($pattern, 0, -1).'$#';
        }

        if (@preg_match($compiled, '') === false) {
            return null;
        }

        if (!empty($params)) {
            preg_match_all('/(?<!\\\)\((?!\?)/', $pattern, $matches);
            $capture_count = count($matches[0]);

            if ($capture_count !== count($params)) {
                return null;
            }
        }

        return [
            'pattern' => $pattern,
            'regex' => $compiled,
            'params' => $params
        ];
    }

    /**
     * Validate action format and return it unchanged for cacheability.
     *
     * Stores actions in their original format to support route caching.
     * Actions are validated but not converted to closures.
     *
     * @param  callable|array|string|null  $action  The action to validate
     *
     * @return callable|array|string|null  Validated action in original format
     */
    private function normalizeAction($action)
    {
        if ($action === null) {
            return null;
        }

        // Check arrays first (controller arrays)
        if (is_array($action) && isset($action[0], $action[1]) && !isset($action[2])) {
            return $action;
        }

        // Check strings next (invokeable classes and plain strings)
        if (is_string($action)) {
            return $action;
        }

        // Finally check callables (closures and other callables)
        if (is_callable($action)) {
            return $action;
        }

        return null;
    }

    /**
     * Execute an action with the provided arguments.
     *
     * @param  callable|array|string  $action  The action to execute
     * @param  array                  $args    Arguments to pass to the action
     *
     * @return void
     */
    private function executeAction($action, array $args): void
    {
        if (is_array($action)) {
            (new $action[0]())->{$action[1]}(...$args);

            return;
        }

        if (is_string($action)) {
            if (method_exists($action, '__invoke')) {
                (new $action())(...$args);

                return;
            }

            echo $action;

            return;
        }

        $action(...$args);
    }

    /**
     * Dispatch the request using O(1) hash map lookup for static routes,
     * falling back to O(n) regex matching for dynamic routes.
     *
     * Performance characteristics:
     * - Static routes: O(1) constant time
     * - Dynamic routes: O(n) where n is number of dynamic routes
     * - Static routes are checked first for optimal performance
     *
     * @return bool  True if route or fallback was executed, false otherwise
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function dispatch(): bool
    {
        $key = $this->request_method.':'.$this->request_path;

        // Check static routes first (O(1))
        if (isset($this->routes['static'][$key])) {
            $this->executeAction($this->routes['static'][$key], $this->args);

            return true;
        }

        // Check dynamic routes (O(n)) - method check before expensive regex
        foreach ($this->routes['dynamic'] as $route) {
            if ($route['method'] !== $this->request_method) {
                continue;
            }

            if (preg_match($route['regex'], $this->request_path, $matches)) {
                $captured_values = array_slice($matches, 1);
                $optional_params = $route['optional_params'] ?? [];
                $params = [];

                foreach ($route['params'] as $index => $param_name) {
                    $value = $captured_values[$index] ?? '';

                    if ($value !== '' || !in_array($param_name, $optional_params)) {
                        $params[$param_name] = $value;
                    }
                }

                $this->executeAction($route['action'], array_merge([$params], $this->args));

                return true;
            }
        }

        // Check regex routes (O(n))
        foreach ($this->routes['regex'] as $route) {
            if ($route['method'] !== $this->request_method) {
                continue;
            }

            if (preg_match($route['regex'], $this->request_path, $matches)) {
                if (!empty($route['params'])) {
                    $params = array_combine($route['params'], array_slice($matches, 1));
                    $this->executeAction($route['action'], array_merge([$params], $this->args));
                } else {
                    $this->executeAction($route['action'], $this->args);
                }

                return true;
            }
        }

        if ($this->not_found_handler !== null) {
            $this->executeAction($this->not_found_handler, $this->args);

            return true;
        }

        return false;
    }

    /**
     * Check if all routes are cacheable (no closures).
     *
     * Routes with closures cannot be cached because closures cannot be serialized
     * in PHP. Only routes using controller arrays [Controller::class, 'method']
     * or invokeable classes Controller::class can be cached.
     *
     * Example:
     * ```php
     * $router = new HandleRoute('GET', '/');
     * $router->get('/users', [UserController::class, 'index']);  // Cacheable
     * $router->get('/posts', function () {});  // Not cacheable
     *
     * if ($router->isCacheable()) {
     *     file_put_contents('cache/routes.php', '<?php return ' . var_export($router->compileRoutes(), true) . ';');
     * }
     * ```
     *
     * @return bool  True if all routes are cacheable, false if any closures are present
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function isCacheable(): bool
    {
        foreach ($this->routes['static'] as $action) {
            if ($action instanceof Closure) {
                return false;
            }
        }

        foreach ($this->routes['dynamic'] as $route) {
            if ($route['action'] instanceof Closure) {
                return false;
            }
        }

        foreach ($this->routes['regex'] as $route) {
            if ($route['action'] instanceof Closure) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compile all routes into a cacheable data structure.
     *
     * Returns an array containing both static and dynamic routes that can be
     * serialized and cached for improved performance across requests.
     *
     * **Important:** Only routes using controller arrays or invokeable classes
     * can be cached. Routes with closures cannot be serialized and will cause
     * this method to throw a RuntimeException.
     *
     * Cacheable route types:
     * - Controller arrays: [UserController::class, 'index']
     * - Invokeable classes: UserController::class
     * - String responses: 'Hello World'
     *
     * Non-cacheable route types:
     * - Closures: function () { echo 'Hello'; }
     *
     * Example:
     * ```php
     * $router = new HandleRoute('GET', '/');
     * $router->get('/users', [UserController::class, 'index']);
     * $router->get('/users/{id}', [UserController::class, 'show']);
     *
     * if ($router->isCacheable()) {
     *     $compiled = $router->compileRoutes();
     *     file_put_contents('cache/routes.php', '<?php return ' . var_export($compiled, true) . ';');
     * }
     * ```
     *
     * @return array  Compiled routes structure with 'static' and 'dynamic' keys
     *
     * @throws RuntimeException  If any routes contain closures that cannot be cached
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function compileRoutes(): array
    {
        if (!$this->isCacheable()) {
            throw new RuntimeException(
                'Cannot compile routes with closures for caching. '.
                'Closures cannot be serialized in PHP. '.
                'Use controller arrays [Controller::class, \'method\'] or '.
                'invokeable classes Controller::class instead.'
            );
        }

        return $this->routes;
    }

    /**
     * Set pre-compiled routes from cache.
     *
     * Allows you to bypass route definition and use pre-compiled routes for improved performance.
     * The routes array should be in the format returned by compileRoutes().
     *
     * Example:
     * ```php
     * $compiled = include 'cache/routes.php';
     * $router = new HandleRoute($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
     * $router->setCachedRoutes($compiled);
     * $router->dispatch();
     * ```
     *
     * @param  array  $routes  Compiled routes array with 'static' and 'dynamic' keys
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function setCachedRoutes(array $routes): HandleRoute
    {
        if (isset($routes['static']) && is_array($routes['static'])) {
            $this->routes['static'] = $routes['static'];
        }

        if (isset($routes['dynamic']) && is_array($routes['dynamic'])) {
            $this->routes['dynamic'] = $routes['dynamic'];
        }

        if (isset($routes['regex']) && is_array($routes['regex'])) {
            $this->routes['regex'] = $routes['regex'];
        }

        return $this;
    }
}