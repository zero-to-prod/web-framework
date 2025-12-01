# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A simple PHP web framework library that provides environment variable loading and management capabilities. The package supports PHP 7.1+ and is designed for broad compatibility across multiple PHP versions.

## Architecture

### Core Component: WebFramework Class

The main `WebFramework` class (`src/WebFramework.php`) provides a fluent interface for:
- Setting the application base path
- Loading environment variables from `.env` files
- Binding environment variables to PHP's global scope (`$_ENV` and `putenv()`)
- Immutable environment variable binding (won't overwrite existing values)

The class uses method chaining and accepts optional callables for customizing behavior:
```php
$framework = new WebFramework($basePath);
$framework
    ->setEnvPath($envPath)
    ->setEnvParser(EnvParser::handle())
    ->setEnvBinder(EnvBinderImmutable::handle())
    ->bindEnv();
```

### Plugin System

The framework uses a plugin-based architecture with two first-party plugins:

1. **EnvParser** (`src/Plugins/EnvParser.php`) - Parses `.env` files into associative arrays
2. **EnvBinderImmutable** (`src/Plugins/EnvBinderImmutable.php`) - Binds variables without overwriting existing ones

Custom plugins can be provided as callables to `setEnvParser()` and `setEnvBinder()`.

### Dependencies

- **zero-to-prod/phpdotenv**: Local path dependency (`../phpdotenv`) for `.env` file parsing
- **zero-to-prod/package-helper**: Used by the documentation publishing bin script

### Multi-Version PHP Support

This project is designed to work across PHP 7.1 through 8.5. Each PHP version has isolated vendor directories (`.vendor/php7.1/`, `.vendor/php8.5/`, etc.) to prevent version conflicts.

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

## Future Development

Two routing design proposals exist in the repository:
- `ROUTING_PROPOSAL.md` - Method-based fluent interface approach
- `CACHEABLE_ROUTING_DESIGN.md` - Performance-focused cacheable routing system

These documents outline potential future features for request/response handling and routing.

## Key Technical Details

- **Namespace**: `Zerotoprod\WebFramework\`
- **Minimum PHP**: 7.1
- **PSR-4 Autoloading**: `src/` maps to `Zerotoprod\WebFramework\`
- **Vendor Directories**: Isolated per PHP version in `.vendor/php{VERSION}/`
- **Dependency**: Local path repository to `../phpdotenv` package
- **Bootstrap Script**: `dock` - Bash wrapper for Docker Compose commands