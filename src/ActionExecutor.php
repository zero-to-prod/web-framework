<?php

namespace Zerotoprod\WebFramework;

use InvalidArgumentException;

/**
 * Executes route actions with proper parameter handling.
 *
 * Separates action execution logic from route matching and collection management.
 * Supports multiple action types: closures, controller arrays, invokable classes, and strings.
 *
 * @link https://github.com/zero-to-prod/web-framework
 */
class ActionExecutor
{
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
    public static function execute($action, array $params, array $args): void
    {
        if (is_array($action)) {
            self::executeControllerArray($action, $params, $args);

            return;
        }

        if (is_string($action)) {
            self::executeString($action, $params, $args);

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
    private static function executeControllerArray(array $action, array $params, array $args): void
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
    private static function executeString(string $action, array $params, array $args): void
    {
        if (method_exists($action, '__invoke')) {
            (new $action())($params, ...$args);

            return;
        }

        echo $action;
    }
}