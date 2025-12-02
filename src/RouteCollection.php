<?php

namespace Zerotoprod\WebFramework;

use BadMethodCallException;
use Closure;
use InvalidArgumentException;
use RuntimeException;

/**
 * Collection of HTTP routes with fluent registration API.
 *
 * Provides Laravel-style routing with inline constraints, optional parameters,
 * and fluent where() chaining.
 *
 * @link https://github.com/zero-to-prod/web-framework
 */
class RouteCollection
{
    /**
     * Registered routes organized by type.
     *
     * @var array
     */
    private $routes = [
        'static' => [],
        'patterned' => []
    ];

    /**
     * Pending route for fluent where() calls.
     *
     * @var Route|null
     */
    private $pending_route;

    /**
     * Fallback handler for 404 responses.
     *
     * @var callable|null
     */
    private $not_found_handler = null;

    /**
     * Define a GET route.
     *
     * @param  string  $uri     The URI pattern
     * @param  mixed   $action  The action to execute
     *
     * @return RouteCollection  Returns $this for method chaining
     */
    public function get(string $uri, $action = null): RouteCollection
    {
        return $this->addRoute('GET', $uri, $action);
    }

    /**
     * Define a POST route.
     *
     * @param  string  $uri     The URI pattern
     * @param  mixed   $action  The action to execute
     *
     * @return RouteCollection  Returns $this for method chaining
     */
    public function post(string $uri, $action = null): RouteCollection
    {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Define a PUT route.
     *
     * @param  string  $uri     The URI pattern
     * @param  mixed   $action  The action to execute
     *
     * @return RouteCollection  Returns $this for method chaining
     */
    public function put(string $uri, $action = null): RouteCollection
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Define a PATCH route.
     *
     * @param  string  $uri     The URI pattern
     * @param  mixed   $action  The action to execute
     *
     * @return RouteCollection  Returns $this for method chaining
     */
    public function patch(string $uri, $action = null): RouteCollection
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Define a DELETE route.
     *
     * @param  string  $uri     The URI pattern
     * @param  mixed   $action  The action to execute
     *
     * @return RouteCollection  Returns $this for method chaining
     */
    public function delete(string $uri, $action = null): RouteCollection
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Define an OPTIONS route.
     *
     * @param  string  $uri     The URI pattern
     * @param  mixed   $action  The action to execute
     *
     * @return RouteCollection  Returns $this for method chaining
     */
    public function options(string $uri, $action = null): RouteCollection
    {
        return $this->addRoute('OPTIONS', $uri, $action);
    }

    /**
     * Define a HEAD route.
     *
     * @param  string  $uri     The URI pattern
     * @param  mixed   $action  The action to execute
     *
     * @return RouteCollection  Returns $this for method chaining
     */
    public function head(string $uri, $action = null): RouteCollection
    {
        return $this->addRoute('HEAD', $uri, $action);
    }

    /**
     * Add regex constraint(s) to the most recent route.
     *
     * @param  string|array  $param    Parameter name or array of name => pattern
     * @param  string|null   $pattern  Regex pattern (if $param is string)
     *
     * @return RouteCollection  Returns $this for method chaining
     *
     * @throws BadMethodCallException  If no pending route
     * @throws InvalidArgumentException  If constraint pattern is invalid
     */
    public function where($param, ?string $pattern = null): RouteCollection
    {
        if ($this->pending_route === null) {
            throw new BadMethodCallException('No pending route for where() constraint. Call an HTTP method first.');
        }

        if (is_array($param)) {
            foreach ($param as $key => $value) {
                $this->validateConstraint($key, $value);
                $this->pending_route->addConstraint($key, $value);
            }
        } else {
            $this->validateConstraint($param, $pattern);
            $this->pending_route->addConstraint($param, $pattern);
        }

        $this->recompileRoute($this->pending_route);

        return $this;
    }

    /**
     * Name the most recent route.
     *
     * @param  string  $name  Route name
     *
     * @return RouteCollection  Returns $this for method chaining
     *
     * @throws BadMethodCallException  If no pending route
     */
    public function name(string $name): RouteCollection
    {
        if ($this->pending_route === null) {
            throw new BadMethodCallException('No pending route for name(). Call an HTTP method first.');
        }

        $this->pending_route->name = $name;

        return $this;
    }

    /**
     * Define a fallback handler for 404 responses.
     *
     * @param  mixed  $action  The action to execute
     *
     * @return RouteCollection  Returns $this for method chaining
     *
     * @throws InvalidArgumentException  If action is invalid
     */
    public function fallback($action): RouteCollection
    {
        $this->finalizePendingRoute();

        if (!$this->isValidAction($action)) {
            throw new InvalidArgumentException('Fallback action is invalid');
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
     */
    public function dispatch(string $method, string $uri, ...$args): bool
    {
        $this->finalizePendingRoute();

        $path = strtok($uri, '?');
        $path = $path !== false ? $path : '';

        $key = $method.':'.$path;

        if (isset($this->routes['static'][$key])) {
            $this->executeAction($this->routes['static'][$key]->action, [], $args);

            return true;
        }

        foreach ($this->routes['patterned'] as $route) {
            if ($route->method !== $method) {
                continue;
            }

            if (preg_match($route->regex, $path, $matches)) {
                $params = $this->extractParams($matches, $route->params, $route->optional_params);
                $this->executeAction($route->action, $params, $args);

                return true;
            }
        }

        if ($this->not_found_handler !== null) {
            $this->executeAction($this->not_found_handler, [], $args);

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
        $this->finalizePendingRoute();

        $all_routes = [];

        foreach ($this->routes['static'] as $route) {
            $all_routes[] = $route;
        }

        foreach ($this->routes['patterned'] as $route) {
            $all_routes[] = $route;
        }

        return $all_routes;
    }

    /**
     * Find matching route for method and URI.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri     Request URI
     *
     * @return Route|null  Matching route or null
     */
    public function matchRoute(string $method, string $uri)
    {
        $this->finalizePendingRoute();

        $path = strtok($uri, '?');
        $path = $path !== false ? $path : '';

        $key = $method.':'.$path;

        if (isset($this->routes['static'][$key])) {
            return $this->routes['static'][$key];
        }

        foreach ($this->routes['patterned'] as $route) {
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
        $this->finalizePendingRoute();

        foreach ($this->getRoutes() as $route) {
            if ($route->method === $method && $route->pattern === $pattern) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if all routes are cacheable.
     *
     * @return bool  True if all routes can be cached
     */
    public function isCacheable(): bool
    {
        $this->finalizePendingRoute();

        foreach ($this->getRoutes() as $route) {
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

        $static_routes = array_map(function ($route) {
            return $route->toArray();
        }, $this->routes['static']);

        $patterned_routes = array_map(function ($route) {
            return $route->toArray();
        }, $this->routes['patterned']);

        return serialize([
            'static' => $static_routes,
            'patterned' => $patterned_routes
        ]);
    }

    /**
     * Load compiled routes from cache.
     *
     * @param  string  $data  Serialized route data
     *
     * @return RouteCollection  Returns $this for method chaining
     */
    public function loadCompiled(string $data): RouteCollection
    {
        $routes = unserialize($data, ['allowed_classes' => true]);

        if (isset($routes['static']) && is_array($routes['static'])) {
            foreach ($routes['static'] as $routeData) {
                $route = Route::fromArray($routeData);
                $key = $route->method.':'.$route->pattern;
                $this->routes['static'][$key] = $route;
            }
        }

        if (isset($routes['patterned']) && is_array($routes['patterned'])) {
            foreach ($routes['patterned'] as $routeData) {
                $this->routes['patterned'][] = Route::fromArray($routeData);
            }
        }

        return $this;
    }

    /**
     * Add a route to the collection.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri     URI pattern
     * @param  mixed   $action  Action to execute
     *
     * @return RouteCollection  Returns $this for method chaining
     *
     * @throws InvalidArgumentException  If action is null or invalid
     */
    private function addRoute(string $method, string $uri, $action): RouteCollection
    {
        $this->finalizePendingRoute();

        if ($action === null) {
            throw new InvalidArgumentException("Action cannot be null for route: {$method} {$uri}");
        }

        if (!$this->isValidAction($action)) {
            throw new InvalidArgumentException("Invalid action type for route: {$method} {$uri}");
        }

        $parsed = $this->parsePattern($uri);

        $route = new Route(
            $method,
            $uri,
            '',
            $parsed['params'],
            $parsed['optional_params'],
            $parsed['constraints'],
            $action
        );

        $route->regex = $this->compileRoute($route);

        if (empty($parsed['params'])) {
            $key = $method.':'.$uri;
            $this->routes['static'][$key] = $route;
        } else {
            $this->pending_route = $route;
        }

        return $this;
    }

    /**
     * Finalize pending route by moving it to storage.
     */
    private function finalizePendingRoute(): void
    {
        if ($this->pending_route !== null) {
            $this->routes['patterned'][] = $this->pending_route;
            $this->pending_route = null;
        }
    }

    /**
     * Parse URI pattern for parameters, constraints, and optional markers.
     *
     * @param  string  $pattern  URI pattern
     *
     * @return array  Parsed pattern data
     */
    private function parsePattern(string $pattern): array
    {
        preg_match_all('/\{([a-zA-Z_]+\w*)(?::(.+?))?(\?)?\}/', $pattern, $matches);

        $params = [];
        $optional_params = [];
        $constraints = [];

        foreach ($matches[0] as $i => $placeholder) {
            $name = $matches[1][$i];
            $constraint = !empty($matches[2][$i]) ? $matches[2][$i] : null;
            $is_optional = !empty($matches[3][$i]);

            $params[] = $name;

            if ($is_optional) {
                $optional_params[] = $name;
            }

            if ($constraint) {
                $constraints[$name] = $constraint;
            }
        }

        return compact('params', 'optional_params', 'constraints');
    }

    /**
     * Compile route pattern to regex.
     *
     * @param  Route  $route  The route to compile
     *
     * @return string  Compiled regex pattern
     */
    private function compileRoute(Route $route): string
    {
        $pattern = $route->pattern;

        preg_match_all('/\{([a-zA-Z_]+\w*)(?::(.+?))?(\?)?\}/', $pattern, $matches);

        $search = [];
        $replace = [];

        foreach ($matches[0] as $i => $placeholder) {
            $name = $matches[1][$i];
            $inline_constraint = !empty($matches[2][$i]) ? $matches[2][$i] : null;
            $is_optional = !empty($matches[3][$i]);

            $constraint = $route->constraints[$name] ?? $inline_constraint ?? '[^/]+';

            if ($is_optional) {
                $search[] = '/'.$placeholder;
                $replace[] = '(?:/('.$constraint.'))?';
            } else {
                $search[] = $placeholder;
                $replace[] = '('.$constraint.')';
            }
        }

        return '#^'.str_replace($search, $replace, $pattern).'$#';
    }

    /**
     * Recompile route after adding constraints.
     *
     * @param  Route  $route  The route to recompile
     */
    private function recompileRoute(Route $route): void
    {
        $route->regex = $this->compileRoute($route);
    }

    /**
     * Extract parameters from regex matches.
     *
     * @param  array  $matches         Regex match results
     * @param  array  $param_names     Parameter names
     * @param  array  $optional_params Optional parameter names
     *
     * @return array  Extracted parameters
     */
    private function extractParams(
        array $matches,
        array $param_names,
        array $optional_params = []
    ): array {
        $captured_values = array_slice($matches, 1);
        $params = [];

        foreach ($param_names as $index => $param_name) {
            $value = $captured_values[$index] ?? '';

            if ($value !== '' || !in_array($param_name, $optional_params, true)) {
                $params[$param_name] = $value;
            }
        }

        return $params;
    }

    /**
     * Execute an action with parameters.
     *
     * @param  mixed  $action  Action to execute
     * @param  array  $params  Route parameters
     * @param  array  $args    Additional arguments
     */
    private function executeAction($action, array $params, array $args): void
    {
        if (is_array($action)) {
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

        $action($params, ...$args);
    }

    /**
     * Validate that action is acceptable.
     *
     * @param  mixed  $action  Action to validate
     *
     * @return bool  True if valid
     */
    private function isValidAction($action): bool
    {
        if (is_array($action) && isset($action[0], $action[1]) && !isset($action[2])) {
            return true;
        }

        if (is_string($action)) {
            return true;
        }

        if (is_callable($action)) {
            return true;
        }

        return false;
    }

    /**
     * Validate regex constraint pattern.
     *
     * @param  string  $param    Parameter name
     * @param  string  $pattern  Regex pattern
     *
     * @throws InvalidArgumentException  If pattern is invalid
     */
    private function validateConstraint(string $param, string $pattern): void
    {
        if (@preg_match('#^'.$pattern.'$#', '') === false) {
            throw new InvalidArgumentException("Invalid regex pattern for constraint '{$param}': {$pattern}");
        }
    }
}
