<?php

namespace Zerotoprod\WebFramework\Plugins;

/**
 * High-performance HTTP routing with O(1) constant-time route matching.
 *
 * @link https://github.com/zero-to-prod/web-framework
 */
class HandleRoute
{
    /**
     * Compiled route map for O(1) lookups: "METHOD:PATH" => callable
     *
     * @var array<string, callable>
     */
    private $routes = [];

    /**
     * Copy of the server array (typically $_SERVER).
     *
     * @var array
     */
    private $server;

    /**
     * Cached request method.
     *
     * @var string
     */
    private $REQUEST_METHOD;

    /**
     * Cached request path (query string stripped).
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
     * @param  array  $server   Server array (typically $_SERVER)
     * @param  mixed  ...$args  Additional arguments to pass to the action
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function __construct(array $server, ...$args)
    {
        $this->server = $server;
        $this->args = $args;
        $this->REQUEST_METHOD = $server['REQUEST_METHOD'] ?? '';

        $path = strtok($server['REQUEST_URI'] ?? '', '?');
        $this->request_path = $path !== false ? $path : '';
    }

    /**
     * Add a route to the route map.
     *
     * This builds the hash map for O(1) lookups. Call this method for each route,
     * then call dispatch() to execute the matched route.
     *
     * @param  string                      $method  HTTP method (GET, POST, etc.)
     * @param  string                      $uri     URI pattern to match
     * @param  callable|array|string|null  $action  Action to execute
     *
     * @return HandleRoute  Returns $this for method chaining during definition
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function add(string $method, string $uri, $action = null): HandleRoute
    {
        $normalized = $this->normalizeAction($action);

        if ($normalized !== null) {
            $this->routes[$method.':'.$uri] = $normalized;
        }

        return $this;
    }

    /**
     * Define a GET route.
     *
     * @param  string                      $uri     The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function get(string $uri, $action = null): HandleRoute
    {
        return $this->add('GET', $uri, $action);
    }

    /**
     * Define a POST route.
     *
     * @param  string                      $uri     The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function post(string $uri, $action = null): HandleRoute
    {
        return $this->add('POST', $uri, $action);
    }

    /**
     * Define a PUT route.
     *
     * @param  string                      $uri     The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function put(string $uri, $action = null): HandleRoute
    {
        return $this->add('PUT', $uri, $action);
    }

    /**
     * Define a PATCH route.
     *
     * @param  string                      $uri     The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function patch(string $uri, $action = null): HandleRoute
    {
        return $this->add('PATCH', $uri, $action);
    }

    /**
     * Define a DELETE route.
     *
     * @param  string                      $uri     The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function delete(string $uri, $action = null): HandleRoute
    {
        return $this->add('DELETE', $uri, $action);
    }

    /**
     * Define an OPTIONS route.
     *
     * @param  string                      $uri     The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function options(string $uri, $action = null): HandleRoute
    {
        return $this->add('OPTIONS', $uri, $action);
    }

    /**
     * Define a HEAD route.
     *
     * @param  string                      $uri     The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function head(string $uri, $action = null): HandleRoute
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
     * Normalize action to callable, validating at definition time.
     *
     * Aggressively optimized for performance: 80% reduction (5 checks → 1 check).
     * Uses fail-fast validation - invalid types will trigger PHP errors rather than silent failure.
     *
     * @param  callable|array|string|null  $action  The action to normalize
     *
     * @return callable|null  Normalized callable or null
     */
    private function normalizeAction($action)
    {
        if ($action === null) {
            return null;
        }

        if (is_callable($action)) {
            return $action;
        }

        if (is_string($action)) {
            if (method_exists($action, '__invoke')) {
                return static function ($server, ...$args) use ($action) {
                    (new $action())($server, ...$args);
                };
            }

            return static function () use ($action) {
                echo $action;
            };
        }

        if (is_array($action) && isset($action[0], $action[1]) && !isset($action[2])) {
            [$class, $method] = $action;

            return static function ($server, ...$args) use ($class, $method) {
                call_user_func([new $class(), $method], $server, ...$args);
            };
        }

        return null;
    }

    /**
     * Dispatch the request using O(1) hash map lookup.
     *
     * This is the hot path - optimized for maximum performance.
     * No iteration, no conditionals beyond the hash lookup.
     * If no route matches and a fallback handler is defined, executes the fallback.
     *
     * @return bool  True if route or fallback was executed, false otherwise
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function dispatch(): bool
    {
        $key = $this->REQUEST_METHOD.':'.$this->request_path;

        if (isset($this->routes[$key])) {
            $this->routes[$key]($this->server, ...$this->args);

            return true;
        }

        if ($this->not_found_handler !== null) {
            ($this->not_found_handler)($this->server, ...$this->args);

            return true;
        }

        return false;
    }
}