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

        $this->request_method = $serverTarget['REQUEST_METHOD'] ?? '';
        $this->request_path = $this->parsePath($serverTarget['REQUEST_URI'] ?? '');
    }

    /**
     * Parse URI path, stripping query string and handling edge cases.
     *
     * @param  string  $uri  The URI to parse
     *
     * @return string  The parsed path (empty string if parsing fails)
     */
    private function parsePath(string $uri): string
    {
        $path = strtok($uri, '?');
        return $path !== false ? $path : '';
    }

    /**
     * Define a GET route.
     *
     * @param  string                      $uri     The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute (callable, [Class::class, 'method'], or string response)
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function get(string $uri, $action = null): HandleRoute
    {
        return $this->match('GET', $uri, $this->normalizeAction($action));
    }

    /**
     * Define a POST route.
     *
     * @param  string                      $uri     The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute (callable, [Class::class, 'method'], or string response)
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function post(string $uri, $action = null): HandleRoute
    {
        return $this->match('POST', $uri, $this->normalizeAction($action));
    }

    /**
     * Define a PUT route.
     *
     * @param  string                      $uri     The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute (callable, [Class::class, 'method'], or string response)
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function put(string $uri, $action = null): HandleRoute
    {
        return $this->match('PUT', $uri, $this->normalizeAction($action));
    }

    /**
     * Define a PATCH route.
     *
     * @param  string                      $uri     The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute (callable, [Class::class, 'method'], or string response)
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function patch(string $uri, $action = null): HandleRoute
    {
        return $this->match('PATCH', $uri, $this->normalizeAction($action));
    }

    /**
     * Define a DELETE route.
     *
     * @param  string                      $uri     The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute (callable, [Class::class, 'method'], or string response)
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function delete(string $uri, $action = null): HandleRoute
    {
        return $this->match('DELETE', $uri, $this->normalizeAction($action));
    }

    /**
     * Define an OPTIONS route.
     *
     * @param  string                      $uri     The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute (callable, [Class::class, 'method'], or string response)
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function options(string $uri, $action = null): HandleRoute
    {
        return $this->match('OPTIONS', $uri, $this->normalizeAction($action));
    }

    /**
     * Define a HEAD route.
     *
     * @param  string                      $uri     The URI pattern to match
     * @param  callable|array|string|null  $action  The action to execute (callable, [Class::class, 'method'], or string response)
     *
     * @return HandleRoute  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function head(string $uri, $action = null): HandleRoute
    {
        return $this->match('HEAD', $uri, $this->normalizeAction($action));
    }

    /**
     * Normalize action to a consistent type, validating controller arrays at definition time.
     *
     * Converts controller arrays to callables immediately, eliminating runtime validation.
     * Invalid controller arrays become null (no-op).
     *
     * @param  callable|array|string|null  $action  The action to normalize
     *
     * @return callable|string|null  Normalized action (callable, string, or null)
     */
    private function normalizeAction($action)
    {
        if (is_array($action)) {
            // Validate controller array format at definition time
            if (count($action) === 2
                && is_string($action[0])
                && is_string($action[1])
                && class_exists($action[0])
                && method_exists($action[0], $action[1])
            ) {
                // Convert to callable - validation happens once, not on every request
                $class = $action[0];
                $method = $action[1];
                $serverTarget = &$this->serverTarget;

                return function () use ($class, $method, &$serverTarget) {
                    call_user_func([new $class(), $method], $serverTarget);
                };
            }

            // Invalid controller array becomes null (no-op)
            return null;
        }

        return $action;
    }

    /**
     * Match a route and execute action if matches current request.
     *
     * Actions are pre-normalized by normalizeAction(), so only callable, string, or null arrive here.
     *
     * @param  string                   $method  The HTTP method to match
     * @param  string                   $uri     The URI pattern to match
     * @param  callable|string|null  $action  The normalized action to execute
     *
     * @return HandleRoute  Returns $this for method chaining
     */
    private function match(string $method, string $uri, $action = null): HandleRoute
    {
        if ($this->route_matched) {
            return $this;
        }

        if ($this->request_method === $method && $this->request_path === $uri) {
            $this->route_matched = true;

            if ($action === null) {
                return $this;
            }

            if (is_callable($action)) {
                $action($this->serverTarget);
            } else {
                echo $action;
            }
        }

        return $this;
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
     * Reset the matched state and re-parse request data.
     *
     * @return HandleRoute  Returns $this for method chaining
     */
    public function reset(): HandleRoute
    {
        $this->route_matched = false;

        $this->request_method = $this->serverTarget['REQUEST_METHOD'] ?? '';
        $this->request_path = $this->parsePath($this->serverTarget['REQUEST_URI'] ?? '');

        return $this;
    }
}
