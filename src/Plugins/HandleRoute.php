<?php

namespace Zerotoprod\WebFramework\Plugins;

/**
 * Simple routing plugin for handling HTTP requests.
 *
 * @link https://github.com/zero-to-prod/web-framework
 */
class HandleRoute
{
    /**
     * Reference to the server array (typically $_SERVER).
     *
     * @var array
     */
    private $serverTarget;

    /**
     * Flag to track if a route has been matched and executed.
     *
     * @var bool
     */
    private $route_matched = false;

    /**
     * Cached request method (parsed once in constructor).
     *
     * @var string
     */
    private $request_method;

    /**
     * Cached request path (parsed once in constructor, query string stripped).
     *
     * @var string
     */
    private $request_path;

    /**
     * Create a new HandleRoute instance.
     *
     * @param  array  $serverTarget  Reference to server array (typically $_SERVER)
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function __construct(array &$serverTarget)
    {
        $this->serverTarget = &$serverTarget;
    }

    /**
     * Define a GET route.
     *
     * @param  string  $uri  The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute (callable, [Class::class, 'method'], or string response)
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function get(string $uri, $action = null): HandleRoute
    {
        return $this->match('GET', $uri, $action);
    }

    /**
     * Define a POST route.
     *
     * @param  string  $uri  The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute (callable, [Class::class, 'method'], or string response)
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function post(string $uri, $action = null): HandleRoute
    {
        return $this->match('POST', $uri, $action);
    }

    /**
     * Define a PUT route.
     *
     * @param  string  $uri  The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute (callable, [Class::class, 'method'], or string response)
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function put(string $uri, $action = null): HandleRoute
    {
        return $this->match('PUT', $uri, $action);
    }

    /**
     * Define a PATCH route.
     *
     * @param  string  $uri  The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute (callable, [Class::class, 'method'], or string response)
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function patch(string $uri, $action = null): HandleRoute
    {
        return $this->match('PATCH', $uri, $action);
    }

    /**
     * Define a DELETE route.
     *
     * @param  string  $uri  The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute (callable, [Class::class, 'method'], or string response)
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function delete(string $uri, $action = null): HandleRoute
    {
        return $this->match('DELETE', $uri, $action);
    }

    /**
     * Define an OPTIONS route.
     *
     * @param  string  $uri  The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute (callable, [Class::class, 'method'], or string response)
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function options(string $uri, $action = null): HandleRoute
    {
        return $this->match('OPTIONS', $uri, $action);
    }

    /**
     * Define a HEAD route.
     *
     * @param  string  $uri  The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute (callable, [Class::class, 'method'], or string response)
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function head(string $uri, $action = null): HandleRoute
    {
        return $this->match('HEAD', $uri, $action);
    }

    /**
     * Match a route and execute action if matches current request.
     *
     * @param  string  $method  The HTTP method to match
     * @param  string  $uri  The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute
     *
     * @return HandleRoute  Returns $this for method chaining
     */
    private function match(string $method, string $uri, $action = null): HandleRoute
    {
        if ($this->route_matched) {
            return $this;
        }

        $request_method = $this->serverTarget['REQUEST_METHOD'] ?? '';
        $request_uri = $this->serverTarget['REQUEST_URI'] ?? '';

        $request_uri = strtok($request_uri, '?');

        if ($request_method === $method && $request_uri === $uri) {
            $this->route_matched = true;
            $this->execute($action);
        }

        return $this;
    }

    /**
     * Execute the action for a matched route.
     *
     * @param  callable|array|string|null  $action  The action to execute
     *
     * @return void
     */
    private function execute($action): void
    {
        if ($action === null) {
            return;
        }

        if (is_array($action) && count($action) === 2) {
            $class = $action[0];
            $method = $action[1];
            (new $class())->$method();
            return;
        }

        if (is_callable($action)) {
            $action();
        } else {
            echo $action;
        }
    }

    /**
     * Check if a route has been matched.
     *
     * @return bool
     */
    public function hasMatched(): bool
    {
        return $this->route_matched;
    }

    /**
     * Reset the matched state.
     *
     * @return HandleRoute  Returns $this for method chaining
     */
    public function reset(): HandleRoute
    {
        $this->route_matched = false;

        return $this;
    }
}
