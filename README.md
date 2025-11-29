# Zerotoprod\WebFramework

![](art/logo.png)

[![Repo](https://img.shields.io/badge/github-gray?logo=github)](https://github.com/zero-to-prod/web-framework)
[![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/zero-to-prod/web-framework/test.yml?label=test)](https://github.com/zero-to-prod/web-framework/actions)
[![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/zero-to-prod/web-framework/backwards_compatibility.yml?label=backwards_compatibility)](https://github.com/zero-to-prod/web-framework/actions)
[![Packagist Downloads](https://img.shields.io/packagist/dt/zero-to-prod/web-framework?color=blue)](https://packagist.org/packages/zero-to-prod/web-framework/stats)
[![php](https://img.shields.io/packagist/php-v/zero-to-prod/web-framework.svg?color=purple)](https://packagist.org/packages/zero-to-prod/web-framework/stats)
[![Packagist Version](https://img.shields.io/packagist/v/zero-to-prod/web-framework?color=f28d1a)](https://packagist.org/packages/zero-to-prod/web-framework)
[![License](https://img.shields.io/packagist/l/zero-to-prod/web-framework?color=pink)](https://github.com/zero-to-prod/web-framework/blob/main/LICENSE.md)
[![wakatime](https://wakatime.com/badge/github/zero-to-prod/web-framework.svg)](https://wakatime.com/badge/github/zero-to-prod/web-framework)
[![Hits-of-Code](https://hitsofcode.com/github/zero-to-prod/web-framework?branch=main)](https://hitsofcode.com/github/zero-to-prod/web-framework/view?branch=main)

## Contents

- [Introduction](#introduction)
- [Requirements](#requirements)
- [Installation](#installation)
- [Documentation Publishing](#documentation-publishing)
    - [Automatic Documentation Publishing](#automatic-documentation-publishing)
- [Usage](#usage)
    - [Environment Variable Management](#environment-variable-management)
    - [Basic Usage](#basic-usage)
    - [Method Chaining](#method-chaining)
    - [Custom Callable Functions](#custom-callable-functions)
    - [Immutable Environment Variables](#immutable-environment-variables)
- [Local Development](./LOCAL_DEVELOPMENT.md)
- [Contributing](#contributing)

## Introduction

A simple web framework for PHP

## Requirements

- PHP 7.1 or higher.

## Installation

Install `Zerotoprod\WebFramework` via [Composer](https://getcomposer.org/):

```bash
composer require zero-to-prod/web-framework
```

This will add the package to your project’s dependencies and create an autoloader entry for it.

## Documentation Publishing

You can publish this README to your local documentation directory.

This can be useful for providing documentation for AI agents.

This can be done using the included script:

```bash
# Publish to default location (./docs/zero-to-prod/web-framework)
vendor/bin/zero-to-prod-web-framework

# Publish to custom directory
vendor/bin/zero-to-prod-web-framework /path/to/your/docs
```

#### Automatic Documentation Publishing

You can automatically publish documentation by adding the following to your `composer.json`:

```json
{
  "scripts": {
    "post-install-cmd": [
      "zero-to-prod-web-framework"
    ],
    "post-update-cmd": [
      "zero-to-prod-web-framework"
    ]
  }
}
```


## Usage

### Environment Variable Management

The `WebFramework` class provides a fluent interface for loading and managing environment variables from `.env` files.

### Basic Usage

```php
use Zerotoprod\WebFramework\WebFramework;

// Create an instance with your application's base path
$framework = new WebFramework(__DIR__);

// Load and bind environment variables
$framework
    ->setEnvPath(__DIR__ . '/.env')
    ->setEnv()
    ->bindEnvsToGlobalsImmutable();

// Access environment variables
echo $_ENV['APP_NAME'];        // Access via $_ENV
echo getenv('APP_ENV');        // Access via getenv()
```

**Your `.env` file:**
```dotenv
APP_NAME=MyApplication
APP_ENV=production
DB_HOST=localhost
DB_PORT=3306
```

### Method Chaining

All methods return the `WebFramework` instance, allowing for fluent method chaining:

```php
$framework = (new WebFramework('/var/www/html'))
    ->setEnvPath('/var/www/html/.env')
    ->setEnv()
    ->bindEnvsToGlobalsImmutable();
```

### Custom Callable Functions

You can provide custom callable functions to control how environment variables are parsed and bound.

#### Custom Environment Parsing

```php
$framework = new WebFramework(__DIR__);

$framework
    ->setEnvPath(__DIR__ . '/.env')
    ->setEnv(function (string $env_path): array {
        // Custom parsing logic
        $contents = file_get_contents($env_path);
        $lines = explode("\n", $contents);
        $env = [];

        foreach ($lines as $line) {
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $env[trim($key)] = trim($value);
            }
        }

        return $env;
    })
    ->bindEnvsToGlobalsImmutable();
```

#### Custom Binding Logic

```php
$framework = new WebFramework(__DIR__);

$framework
    ->setEnvPath(__DIR__ . '/.env')
    ->setEnv()
    ->bindEnvsToGlobalsImmutable(function (array $env): void {
        // Custom binding logic
        foreach ($env as $key => $value) {
            // Only bind variables with a specific prefix
            if (strpos($key, 'APP_') === 0) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    });
```

### Immutable Environment Variables

The `bindEnvsToGlobalsImmutable()` method ensures that existing environment variables are never overwritten:

```php
// Set an existing environment variable
$_ENV['APP_ENV'] = 'development';
putenv('APP_ENV=development');

// Attempt to load from .env file (containing APP_ENV=production)
$framework = (new WebFramework(__DIR__))
    ->setEnvPath(__DIR__ . '/.env')
    ->setEnv()
    ->bindEnvsToGlobalsImmutable();

// The original value is preserved
echo $_ENV['APP_ENV'];  // Outputs: development (not production)
```

This immutability applies to variables that exist in either:
- The `$_ENV` superglobal array
- The system environment (accessible via `getenv()`)

**Use Case:** This behavior is useful for:
- Overriding configuration in different environments
- Respecting system-level environment variables
- Preventing accidental overwrites of critical settings

## Contributing

Contributions, issues, and feature requests are welcome!
Feel free to check the [issues](https://github.com/zero-to-prod/web-framework/issues) page if you want to contribute.

1. Fork the repository.
2. Create a new branch (`git checkout -b feature-branch`).
3. Commit changes (`git commit -m 'Add some feature'`).
4. Push to the branch (`git push origin feature-branch`).
5. Create a new Pull Request.
