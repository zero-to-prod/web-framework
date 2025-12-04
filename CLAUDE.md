# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A PHP web framework library providing HTTP routing and environment management. The package supports PHP 7.1+ with broad multi-version compatibility.

Core features:
- **HTTP Routing**: High-performance router with three-level indexing (static → prefix → method)
- **Environment Management**: `.env` file loading with immutable bindings
- **Middleware**: Dual support for PSR-15 and legacy variadic middleware
- **Route Caching**: Production-optimized serialization with automatic cache management

## Architecture

### Router System (`src/Router.php`)

The `Router` class is the primary component, providing:

**Three-Level Route Indexing:**
1. **Static Index** (O(1)): Hash map `method:path` → Route for exact matches
2. **Prefix Index** (O(1) + O(n)): Hash map `method:prefix` → [Routes] for dynamic routes with common prefixes
3. **Method Index** (O(n)): Hash map `method` → [Routes] fallback for complex patterns

**Key Features:**
- RESTful resource routes via `resource()` method
- Route groups with prefix and middleware stacking
- Named routes for URL generation via `route()` method
- Automatic environment-aware caching via `autoCache()`
- Constraint validation (inline: `{id:\d+}` or fluent: `where()`)
- Fallback handlers for 404 responses

**Route Compilation:**
- `RouteCompiler` (`src/RouteCompiler.php`): Converts patterns to regex with parameter extraction
- `Route` (`src/Route.php`): Value object containing compiled route metadata and cached middleware pipeline
- Middleware pipelines are pre-compiled and stored on `Route` objects for performance

**Middleware System:**
- PSR-15 middleware: `RequestHandler` (`src/Http/RequestHandler.php`) wraps callables for PSR-15 compatibility
- Variadic middleware: Legacy `function($next, ...$context)` format
- Both types can be mixed freely (router automatically detects and wraps)
- Global middleware applies to all routes, per-route middleware is route-specific
- Nested groups stack middleware and prefixes

### WebFramework Class (`src/WebFramework.php`)

Environment and application bootstrapping:
```php
$framework = new WebFramework($basePath);
$framework
    ->setEnvPath($envPath)
    ->setEnvParser(EnvParser::handle())
    ->setEnvBinder(EnvBinderImmutable::handle())
    ->bindEnv();
```

**EnvBinderImmutable** (`src/Plugins/EnvBinderImmutable.php`):
- Binds environment variables to `$_ENV` and `putenv()`
- Immutable: Never overwrites existing environment variables
- Used by `autoCache()` to detect production vs development environments

### Dependencies

- **zero-to-prod/phpdotenv**: Local path dependency for `.env` parsing
- **zero-to-prod/package-helper**: Documentation publishing
- **psr/http-server-middleware**: PSR-15 middleware interface
- **nyholm/psr7**: PSR-7 HTTP message implementation
- **nyholm/psr7-server**: PSR-7 server request creation

### Multi-Version PHP Support

Designed for PHP 7.1-8.5 compatibility. Each version has isolated vendor directories (`.vendor/php7.1/`, `.vendor/php8.5/`) to prevent conflicts.

## Development Commands

### Initial Setup

```bash
# Initialize .env file from template
sh dock init

# Install dependencies for the current PHP version (set in .env)
sh dock composer install
```

### Running Tests

```bash
# Run tests for the PHP version specified in .env
sh dock test

# Run tests with PHPUnit options
sh dock test --filter=EnvTest

# Run full test suite across ALL PHP versions (7.1-8.5)
sh test.sh
```

### Managing Dependencies

```bash
# Update dependencies for current PHP version
sh dock composer update

# Install/update for a specific PHP version
sh dock composer8.1 composer update
sh dock composer7.4 composer install
```

### Working with Specific PHP Versions

```bash
# Run commands in a specific PHP version container
sh dock php8.1 bash
sh dock php7.1 vendor/bin/phpunit

# Access the debug container (with Xdebug)
sh dock debug8.1 bash
```

### Configuration

The `.env` file controls which PHP version is used for local development:
```dotenv
PHP_VERSION=8.1
PHP_DEBUG=8.1
PHP_COMPOSER=8.1
```

Change these values and re-run `sh dock composer install` to switch PHP versions.

## Testing

### Test Structure

- Test suite: PHPUnit (located in `tests/`)
- Test namespace: `Tests\` (PSR-4 autoloaded to `tests/`)
- Configuration: `phpunit.xml`
- Test naming: All test files must end with `Test.php`
- Base test class: `Tests\TestCase` (if present)

### Running Single Tests

```bash
# Run a specific test method
sh dock test --filter=env_path_set_returns_instance_for_chaining

# Run a specific test file
sh dock test tests/Unit/EnvTest.php
```

### Test Patterns

Tests use PHPUnit's `@test` annotation and follow these patterns:
- Method naming: `descriptive_test_name_in_snake_case`
- Setup/teardown: Use `setUp()` and `tearDown()` to manage test environment
- Environment cleanup: Always clean up `$_ENV` and `putenv()` in `tearDown()`
- Temporary files: Use `tempnam()` and `unlink()` for file-based tests

## Docker Services

The `docker-compose.yml` defines services for each PHP version with three variants:
- `php{VERSION}`: Base PHP environment for running tests
- `debug{VERSION}`: PHP with Xdebug enabled
- `composer{VERSION}`: PHP with Composer for dependency management

All services mount:
- `./` to `/app` (application code)
- `./.vendor/php{VERSION}` to `/app/vendor` (version-specific dependencies)

## Documentation Publishing

The package includes a bin script (`bin/web-framework`) that publishes the README to a local documentation directory:

```bash
# Publish to default location (./docs/zero-to-prod/web-framework)
vendor/bin/zero-to-prod-web-framework

# Publish to custom directory
vendor/bin/zero-to-prod-web-framework /path/to/docs
```

This can be automated via Composer scripts (see README.md).

## Working with Router Code

### Router Simplification Principles

The Router has undergone extensive simplification (see plan at `~/.claude/plans/flickering-napping-prism.md`). When modifying Router code:

1. **Extract helper methods**: Prefer small, focused internal methods over inline duplication
2. **Use compound booleans**: Consolidate multiple if-blocks into single boolean expressions with guard clauses
3. **Strategy pattern for type dispatch**: Separate execution paths (array actions, invokable, callable) into distinct methods
4. **Centralize pattern construction**: Use helper methods for repeated string patterns (index keys, regex, placeholders)
5. **Index efficiency**: Maintain O(1) lookups where possible (static index, prefix index, pattern index)

**Current test coverage**: 181 tests with 282 assertions. All changes must maintain full backward compatibility.

### Route Caching Behavior

- `isCacheable()`: Returns false if any route or middleware uses closures
- `compile()`: Serializes routes, global middleware, and named routes
- `loadCompiled()`: Deserializes and restores all route state
- `autoCache()`: Automatically manages cache based on `APP_ENV` (default: production only)

Closures cannot be cached due to PHP serialization limitations.

## Key Technical Details

- **Namespace**: `Zerotoprod\WebFramework\`
- **Minimum PHP**: 7.1
- **PSR-4 Autoloading**: `src/` maps to `Zerotoprod\WebFramework\`
- **Vendor Directories**: Isolated per PHP version in `.vendor/php{VERSION}/`
- **Local Dependencies**: `../phpdotenv` via Composer path repository
- **Bootstrap Script**: `dock` - Bash wrapper for Docker Compose commands
- **Test Count**: 181 tests, 282 assertions across all PHP versions