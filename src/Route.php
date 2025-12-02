<?php

namespace Zerotoprod\WebFramework;

use Closure;

/**
 * Represents a single HTTP route.
 *
 * Value object containing all route metadata including method, pattern,
 * compiled regex, parameters, constraints, and action.
 *
 * @link https://github.com/zero-to-prod/web-framework
 */
class Route
{
    /** @var string */
    public $method;

    /** @var string */
    public $pattern;

    /** @var string */
    public $regex;

    /** @var array */
    public $params;

    /** @var array */
    public $optional_params;

    /** @var array */
    public $constraints;

    /** @var mixed */
    public $action;

    /** @var string|null */
    public $name;

    public function __construct(
        string $method,
        string $pattern,
        string $regex,
        array $params,
        array $optional_params,
        array $constraints,
        $action,
        $name = null
    ) {
        $this->method = $method;
        $this->pattern = $pattern;
        $this->regex = $regex;
        $this->params = $params;
        $this->optional_params = $optional_params;
        $this->constraints = $constraints;
        $this->action = $action;
        $this->name = $name;
    }

    /**
     * Check if this route matches the given URI.
     *
     * @param  string  $uri  The URI to match against
     *
     * @return bool  True if route matches
     */
    public function matches(string $uri): bool
    {
        return preg_match($this->regex, $uri) === 1;
    }

    /**
     * Extract parameters from the given URI.
     *
     * @param  string  $uri  The URI to extract parameters from
     *
     * @return array  Associative array of parameter names to values
     */
    public function extractParams(string $uri): array
    {
        if (!preg_match($this->regex, $uri, $matches)) {
            return [];
        }

        $captured_values = array_slice($matches, 1);
        $params = [];

        foreach ($this->params as $index => $param_name) {
            $value = $captured_values[$index] ?? '';

            if ($value !== '' || !in_array($param_name, $this->optional_params, true)) {
                $params[$param_name] = $value;
            }
        }

        return $params;
    }

    /**
     * Create a new route with an additional constraint.
     *
     * @param  string|array  $param    Parameter name or array of constraints
     * @param  string|null   $pattern  Regex pattern (if $param is string)
     *
     * @return Route  New route instance with updated constraint
     */
    public function withConstraint($param, $pattern = null): Route
    {
        if (is_array($param)) {
            return $this->withConstraints($param);
        }

        $new_constraints = array_merge($this->constraints, [$param => $pattern]);
        $compiled = RouteCompiler::compile($this->pattern, $new_constraints);

        return new self(
            $this->method,
            $this->pattern,
            $compiled['regex'],
            $this->params,
            $this->optional_params,
            $new_constraints,
            $this->action,
            $this->name
        );
    }

    /**
     * Create a new route with multiple constraints.
     *
     * @param  array  $constraints  Array of param => pattern
     *
     * @return Route  New route instance with updated constraints
     */
    public function withConstraints(array $constraints): Route
    {
        $new_constraints = array_merge($this->constraints, $constraints);
        $compiled = RouteCompiler::compile($this->pattern, $new_constraints);

        return new self(
            $this->method,
            $this->pattern,
            $compiled['regex'],
            $this->params,
            $this->optional_params,
            $new_constraints,
            $this->action,
            $this->name
        );
    }

    /**
     * Create a new route with a name.
     *
     * @param  string  $name  Route name
     *
     * @return Route  New route instance with name
     */
    public function withName(string $name): Route
    {
        return new self(
            $this->method,
            $this->pattern,
            $this->regex,
            $this->params,
            $this->optional_params,
            $this->constraints,
            $this->action,
            $name
        );
    }

    /**
     * Check if this route is cacheable (no closures).
     *
     * @return bool  True if route can be cached
     */
    public function isCacheable(): bool
    {
        return !($this->action instanceof Closure);
    }

    /**
     * Convert route to array for serialization.
     *
     * @return array  Route data as associative array
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'pattern' => $this->pattern,
            'regex' => $this->regex,
            'params' => $this->params,
            'optional_params' => $this->optional_params,
            'constraints' => $this->constraints,
            'action' => $this->action,
            'name' => $this->name
        ];
    }

    /**
     * Create route from array data.
     *
     * @param  array  $data  Route data from toArray()
     *
     * @return Route  New route instance
     */
    public static function fromArray(array $data): Route
    {
        return new self(
            $data['method'],
            $data['pattern'],
            $data['regex'],
            $data['params'],
            $data['optional_params'],
            $data['constraints'],
            $data['action'],
            $data['name'] ?? null
        );
    }
}
