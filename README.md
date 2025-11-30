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
    - [Environment Variables](#environment-variables)
        - [Overview](#overview)
        - [Quick Start](#quick-start)
        - [Basic Usage](#basic-usage)
        - [Method Chaining](#method-chaining)
        - [Custom Target Environment](#custom-target-environment)
        - [Plugin System](#plugin-system)
        - [Immutable Environment Variables](#immutable-environment-variables)
        - [Order Independence](#order-independence)
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
      "web-framework"
    ],
    "post-update-cmd": [
      "web-framework"
    ]
  }
}
```


## Usage

### Environment Variables

#### Overview

The `WebFramework` class provides a fluent builder interface for loading and managing environment variables from `.env` files.

#### Quick Start

The fastest way to get started is using the `setEnvDefaults()` method, which automatically configures:
- Target environment: `$_ENV` global
- Environment file path: `{basePath}/.env`
- Parser: `EnvParser` plugin
- Binder: `EnvBinderImmutable` plugin

```php
use Zerotoprod\WebFramework\WebFramework;

// Create and load with defaults in one go
$framework = (new WebFramework(__DIR__))
    ->setEnvDefaults()
    ->loadEnv();

// Access environment variables
echo $_ENV['APP_NAME'];
echo getenv('APP_ENV');
```

**Your `.env` file:**
```dotenv
APP_NAME=MyApplication
APP_ENV=production
DB_HOST=localhost
DB_PORT=3306
```

#### Basic Usage

```php
use Zerotoprod\WebFramework\WebFramework;
use Zerotoprod\WebFramework\Plugins\EnvParser;
use Zerotoprod\WebFramework\Plugins\EnvBinderImmutable;

// Create an instance with your application's base path
$framework = new WebFramework(__DIR__);

// Configure and run the environment loader
$framework
    ->setEnvPath(__DIR__ . '/.env')
    ->setEnvParser(EnvParser::handle())
    ->setEnvBinder(EnvBinderImmutable::handle())
    ->loadEnv();

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

#### Method Chaining

The builder pattern allows you to configure all options before executing with `loadEnv()`:

```php
$framework = (new WebFramework('/var/www/html'))
    ->setEnvPath('/var/www/html/.env')
    ->setEnvParser(EnvParser::handle())
    ->setEnvBinder(EnvBinderImmutable::handle())
    ->loadEnv();
```

#### Custom Target Environment

You can specify a custom environment array for testing or isolation using `setEnvTarget()`:

```php
// Default: uses global $_ENV
$framework = new WebFramework(__DIR__);
$framework->setEnvDefaults()->loadEnv();

// Custom: bind to your own array
$customEnv = [];
$framework = (new WebFramework(__DIR__))
    ->setEnvTarget($customEnv)  // Pass by reference
    ->setEnvPath(__DIR__ . '/.env')
    ->setEnvParser(EnvParser::handle())
    ->setEnvBinder(EnvBinderImmutable::handle())
    ->loadEnv();

// Variables are now in $customEnv instead of $_ENV
echo $customEnv['APP_NAME'];

// Note: $customEnv is passed by reference and will be modified directly
```

This is particularly useful for:
- Unit testing without polluting the global environment
- Isolating environment variables between different parts of your application
- Creating multiple independent environment configurations

#### Plugin System

The framework uses a plugin-based architecture with first-party plugins included.

##### First-Party Plugins

**EnvParser** - Parses `.env` files:
```php
use Zerotoprod\WebFramework\Plugins\EnvParser;

$framework->setEnvParser(EnvParser::handle());
```

**EnvBinderImmutable** - Binds variables without overwriting existing ones:
```php
use Zerotoprod\WebFramework\Plugins\EnvBinderImmutable;

$framework->setEnvBinder(EnvBinderImmutable::handle());
```

##### Custom Plugins

You can create custom plugins by providing callables.

**Custom Parser Plugin:**
```php
$framework = new WebFramework(__DIR__);

$framework
    ->setEnvPath(__DIR__ . '/.env')
    ->setEnvParser(function (string $env_path): array {
        // Custom parsing logic
        $contents = file_get_contents($env_path);
        $lines = explode("\n", $contents);
        $parsed_env = [];

        foreach ($lines as $line) {
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $parsed_env[trim($key)] = trim($value);
            }
        }

        return $parsed_env;
    })
    ->setEnvBinder(EnvBinderImmutable::handle())
    ->loadEnv();
```

**Custom Binder Plugin:**
```php
$framework = new WebFramework(__DIR__);

$framework
    ->setEnvPath(__DIR__ . '/.env')
    ->setEnvParser(EnvParser::handle())
    ->setEnvBinder(function (array $parsed_env, array &$target_env): void {
        // Custom binding logic - only bind APP_* variables
        foreach ($parsed_env as $key => $value) {
            if (strpos($key, 'APP_') === 0) {
                $target_env[$key] = $value;
                putenv("$key=$value");
            }
        }
    })
    ->loadEnv();
```

#### Immutable Environment Variables

The `EnvBinderImmutable` plugin ensures that existing environment variables are never overwritten:

```php
use Zerotoprod\WebFramework\Plugins\EnvParser;
use Zerotoprod\WebFramework\Plugins\EnvBinderImmutable;

// Set an existing environment variable
$_ENV['APP_ENV'] = 'development';
putenv('APP_ENV=development');

// Attempt to load from .env file (containing APP_ENV=production)
$framework = (new WebFramework(__DIR__))
    ->setEnvPath(__DIR__ . '/.env')
    ->setEnvParser(EnvParser::handle())
    ->setEnvBinder(EnvBinderImmutable::handle())
    ->loadEnv();

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

#### Order Independence

Configuration methods can be called in any order - only `loadEnv()` executes the workflow:

```php
// These are equivalent:
$framework->setEnvPath('.env')->setEnvParser(EnvParser::handle())->setEnvBinder(EnvBinderImmutable::handle())->loadEnv();
$framework->setEnvBinder(EnvBinderImmutable::handle())->setEnvParser(EnvParser::handle())->setEnvPath('.env')->loadEnv();
```

## Contributing

Contributions, issues, and feature requests are welcome!
Feel free to check the [issues](https://github.com/zero-to-prod/web-framework/issues) page if you want to contribute.

1. Fork the repository.
2. Create a new branch (`git checkout -b feature-branch`).
3. Commit changes (`git commit -m 'Add some feature'`).
4. Push to the branch (`git push origin feature-branch`).
5. Create a new Pull Request.
