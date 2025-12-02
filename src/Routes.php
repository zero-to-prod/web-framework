<?php

namespace Zerotoprod\WebFramework;

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
    /**
     * Create a new route collection.
     *
     * @return RouteCollection  New route collection instance
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public static function collect(): RouteCollection
    {
        return new RouteCollection();
    }

    /**
     * Dispatch a request against a route collection.
     *
     * @param  RouteCollection  $routes  The route collection
     * @param  string           $method  HTTP method (GET, POST, etc.)
     * @param  string           $uri     Request URI
     * @param  mixed            ...$args Additional arguments to pass to action
     *
     * @return bool  True if route was matched and executed
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public static function dispatch(RouteCollection $routes, string $method, string $uri, ...$args): bool
    {
        return $routes->dispatch($method, $uri, ...$args);
    }
}
