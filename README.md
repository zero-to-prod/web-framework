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
    - [HTTP Routing](#http-routing)
        - [Overview](#overview-1)
        - [Quick Start](#quick-start-1)
        - [Supported HTTP Methods](#supported-http-methods)
        - [Action Types](#action-types)
        - [Accessing Request Data](#accessing-request-data)
        - [Method Chaining](#method-chaining-1)
        - [Route Matching](#route-matching)
        - [Checking for Matches](#checking-for-matches)
        - [Resetting Router State](#resetting-router-state)
        - [Advanced: Duplicate Routes](#advanced-duplicate-routes)
        - [Performance Characteristics](#performance-characteristics)
        - [Complete Example](#complete-example)
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

### HTTP Routing

#### Overview

The `HandleRoute` plugin provides high-performance HTTP routing with **O(1) constant-time route matching** using hash map lookups. This means routing performance remains constant regardless of the number of routes defined - whether you have 10 routes or 10,000 routes.

#### Quick Start

```php
use Zerotoprod\WebFramework\Plugins\HandleRoute;

// Create router with server array
$router = new HandleRoute($_SERVER);

// Define routes
$router->get('/users', function ($server) {
    echo 'List of users';
});

$router->post('/users', function ($server) {
    echo 'Create user';
});

// Dispatch the matching route
$router->dispatch();
```

#### Supported HTTP Methods

The router supports all standard HTTP methods:

```php
$router->get('/resource', $action);      // GET requests
$router->post('/resource', $action);     // POST requests
$router->put('/resource', $action);      // PUT requests
$router->patch('/resource', $action);    // PATCH requests
$router->delete('/resource', $action);   // DELETE requests
$router->options('/resource', $action);  // OPTIONS requests
$router->head('/resource', $action);     // HEAD requests
```

#### Action Types

Routes support three types of actions:

##### 1. Closures

```php
$router->get('/hello', function ($server) {
    echo "Hello, World!";
    echo "Request method: " . $server['REQUEST_METHOD'];
});
```

##### 2. Controller Arrays

```php
class UserController {
    public function index(array $server) {
        echo "User list";
    }

    public function show(array $server) {
        echo "Show user";
    }
}

$router->get('/users', [UserController::class, 'index']);
$router->get('/users/show', [UserController::class, 'show']);
```

##### 3. String Responses

```php
$router->get('/status', 'OK');
$router->get('/version', 'v1.0.0');
```

#### Accessing Request Data

All actions receive the server array as their first parameter:

```php
$router->post('/api/data', function ($server) {
    $method = $server['REQUEST_METHOD'];  // POST
    $uri = $server['REQUEST_URI'];        // /api/data
    $host = $server['HTTP_HOST'];         // example.com

    // Access any $_SERVER data
    if (isset($server['HTTP_AUTHORIZATION'])) {
        $token = $server['HTTP_AUTHORIZATION'];
    }
});
```

The server array is passed by reference, allowing modifications:

```php
$router->get('/test', function (&$server) {
    $server['CUSTOM_DATA'] = 'modified';
});

echo $_SERVER['CUSTOM_DATA']; // 'modified'
```

#### Method Chaining

Routes can be defined using method chaining:

```php
$router = new HandleRoute($_SERVER);

$router
    ->get('/', 'Home Page')
    ->get('/about', 'About Us')
    ->get('/contact', 'Contact')
    ->post('/contact', function ($server) {
        echo 'Processing contact form';
    })
    ->dispatch();
```

#### Route Matching

Routes are matched using **exact** method and path comparison:

```php
// Case-sensitive path matching
$router->get('/Users', $action);  // Only matches /Users
$router->get('/users', $action);  // Only matches /users

// Method must match exactly
$router->get('/data', $action);   // Only matches GET requests
$router->post('/data', $action);  // Only matches POST requests
```

Query strings are automatically stripped:

```php
// Request: GET /search?q=test&page=2
$router->get('/search', function ($server) {
    // This route matches!
    // Access query string via $server['QUERY_STRING']
});
```

#### Checking for Matches

```php
$router->get('/home', function () {
    echo 'Home page';
})->dispatch();

if ($router->hasMatched()) {
    echo 'Route was found and executed';
} else {
    echo '404 - Not Found';
}
```

#### Resetting Router State

The `reset()` method clears the matched state and re-parses the server array:

```php
$router = new HandleRoute($_SERVER);

$router->get('/first', function () {
    echo 'First route';
})->dispatch();

// Modify the server array
$_SERVER['REQUEST_URI'] = '/second';
$router->reset();

// Define and dispatch new route
$router->get('/second', function () {
    echo 'Second route';
})->dispatch();
```

#### Advanced: Duplicate Routes

When the same route is defined multiple times, **the last definition wins**:

```php
$router->get('/users', function () {
    echo 'First handler';
});

$router->get('/users', function () {
    echo 'Second handler';  // This one executes
});

$router->dispatch(); // Outputs: "Second handler"
```

This provides predictable route override behavior.

#### Performance Characteristics

The routing implementation uses hash map lookups for **O(1) constant-time performance**:

| Number of Routes | Lookup Time | Performance  |
|------------------|-------------|--------------|
| 10 routes        | 1 lookup    | **Constant** |
| 100 routes       | 1 lookup    | **Constant** |
| 1,000 routes     | 1 lookup    | **Constant** |
| 10,000 routes    | 1 lookup    | **Constant** |

**Key Benefits:**
- No performance degradation as routes increase
- Single hash lookup regardless of route count
- Pre-validated actions eliminate runtime checks
- Zero iteration overhead

**Real-world impact:** At 1,000 requests/second with 200 routes:
- Hash map router: **1,000 lookups/second**
- Sequential router: **100,000 comparisons/second** (100x more operations)

#### Complete Example

```php
use Zerotoprod\WebFramework\Plugins\HandleRoute;

class HomeController {
    public function index(array $server) {
        echo "<h1>Welcome Home</h1>";
    }
}

class ApiController {
    public function users(array $server) {
        header('Content-Type: application/json');
        echo json_encode(['users' => ['Alice', 'Bob']]);
    }

    public function createUser(array $server) {
        header('Content-Type: application/json');
        echo json_encode(['message' => 'User created']);
    }
}

// Initialize router
$router = new HandleRoute($_SERVER);

// Define routes
$router
    ->get('/', [HomeController::class, 'index'])
    ->get('/api/users', [ApiController::class, 'users'])
    ->post('/api/users', [ApiController::class, 'createUser'])
    ->get('/about', 'About Us - Version 1.0')
    ->get('/health', function ($server) {
        echo json_encode([
            'status' => 'healthy',
            'timestamp' => time()
        ]);
    })
    ->dispatch();

// Handle 404
if (!$router->hasMatched()) {
    http_response_code(404);
    echo '404 - Page Not Found';
}
```

## Contributing

Contributions, issues, and feature requests are welcome!
Feel free to check the [issues](https://github.com/zero-to-prod/web-framework/issues) page if you want to contribute.

1. Fork the repository.
2. Create a new branch (`git checkout -b feature-branch`).
3. Commit changes (`git commit -m 'Add some feature'`).
4. Push to the branch (`git push origin feature-branch`).
5. Create a new Pull Request.
