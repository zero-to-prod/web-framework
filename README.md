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
        - [Usage](#usage-1)
        - [Immutable Binding](#immutable-binding)
        - [Custom Target Array](#custom-target-array)
        - [Return Value](#return-value)
    - [WebFramework Core](#webframework-core)
        - [Overview](#overview-1)
        - [Basic Usage](#basic-usage)
        - [Environment Management](#environment-management)
        - [Server Management](#server-management)
        - [Container](#container)
        - [Context Callback](#context-callback)
        - [Method Chaining](#method-chaining)
    - [HTTP Routing](#http-routing)
        - [Overview](#overview-2)
        - [Quick Start](#quick-start)
        - [Supported HTTP Methods](#supported-http-methods)
        - [Action Types](#action-types)
        - [Dynamic Routes with Parameters](#dynamic-routes-with-parameters)
        - [Inline Constraints](#inline-constraints)
        - [Fluent Where Constraints](#fluent-where-constraints)
        - [Optional Parameters](#optional-parameters)
        - [Route Naming](#route-naming)
        - [Additional Arguments (Dependency Injection)](#additional-arguments-dependency-injection)
        - [404 Fallback Handler](#404-fallback-handler)
        - [Middleware](#middleware)
        - [Route Caching](#route-caching)
        - [Method Chaining](#method-chaining-1)
        - [Route Matching Behavior](#route-matching-behavior)
        - [Static vs Dynamic Route Priority](#static-vs-dynamic-route-priority)
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

The `EnvBinderImmutable` plugin provides a simple static method for parsing and binding environment variables from `.env` files.

#### Usage

```php
use Zerotoprod\WebFramework\Plugins\EnvBinderImmutable;

// Read .env file content
$env_content = file_get_contents(__DIR__ . '/.env');

// Parse and bind to $_ENV immutably
$parsed = EnvBinderImmutable::parseFromString($env_content, $_ENV);

// Access environment variables
echo $_ENV['APP_NAME'];    // via $_ENV
echo getenv('APP_ENV');    // via getenv()
```

**Your `.env` file:**
```dotenv
APP_NAME=MyApplication
APP_ENV=production
DB_HOST=localhost
DB_PORT=3306
```

#### Immutable Binding

The plugin ensures existing environment variables are never overwritten:

```php
// Set an existing variable
$_ENV['APP_ENV'] = 'development';
putenv('APP_ENV=development');

// Load from .env file (containing APP_ENV=production)
$env_content = "APP_ENV=production\nDB_HOST=localhost";
EnvBinderImmutable::parseFromString($env_content, $_ENV);

// Original value is preserved
echo $_ENV['APP_ENV'];  // Outputs: development (not production)
echo $_ENV['DB_HOST'];  // Outputs: localhost (newly added)
```

Variables are protected if they exist in either `$_ENV` or `getenv()`.

#### Custom Target Array

Use a custom array instead of `$_ENV` for testing or isolation:

```php
$custom_env = [];
$env_content = file_get_contents(__DIR__ . '/.env');

EnvBinderImmutable::parseFromString($env_content, $custom_env);

// Variables are in $custom_env, not $_ENV
echo $custom_env['APP_NAME'];
```

#### Return Value

The method returns the parsed array for inspection:

```php
$env_content = "APP_NAME=MyApp\nDB_HOST=localhost";
$parsed = EnvBinderImmutable::parseFromString($env_content, $_ENV);

// $parsed contains all parsed variables
// ['APP_NAME' => 'MyApp', 'DB_HOST' => 'localhost']
```

### WebFramework Core

#### Overview

The `WebFramework` class provides a central container for managing environment variables, server context, and dependency injection.

#### Basic Usage

```php
use Zerotoprod\WebFramework\WebFramework;

// Create framework instance
$framework = new WebFramework(__DIR__);

// Store environment and server arrays
$framework->setEnv($_ENV);
$framework->setServer($_SERVER);
```

#### Environment Management

Store and retrieve environment arrays:

```php
$env = ['APP_ENV' => 'production'];
$framework->setEnv($env);

// Retrieve the environment array
$stored_env = $framework->getEnv();
// Returns: ['APP_ENV' => 'production']
```

**Note:** `getEnv()` throws `RuntimeException` if not set.

#### Server Management

Store and retrieve server arrays:

```php
$server = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'];
$framework->setServer($server);

// Retrieve the server array
$stored_server = $framework->getServer();
// Returns: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']
```

**Note:** `getServer()` throws `RuntimeException` if not set.

#### Container

Store a PSR-11 container instance:

```php
$container = new YourContainer();
$framework->setContainer($container);

// Retrieve the container
$stored_container = $framework->container();
```

**Note:** `container()` throws `RuntimeException` if not set.

#### Context Callback

Execute callbacks with the framework instance:

```php
$framework->context(function ($fw) {
    // Access framework methods within callback
    $env = $fw->getEnv();
    $server = $fw->getServer();
});
```

#### Method Chaining

All setters return the framework instance for chaining:

```php
$framework = (new WebFramework(__DIR__))
    ->setEnv($_ENV)
    ->setServer($_SERVER)
    ->setContainer($container)
    ->context(function ($fw) {
        // Configure something
    });
```

### HTTP Routing

#### Overview

The routing system provides a fluent, Laravel-style API for defining HTTP routes with support for:
- **Static routes:** **O(1) constant-time** hash map lookups
- **Dynamic routes:** Pattern-based matching with named parameters
- **Inline constraints:** `{id:\d+}` syntax for parameter validation
- **Optional parameters:** `{name?}` syntax
- **Where constraints:** Fluent `where()` chaining for parameter rules
- **Route naming:** Named routes for URL generation
- **Route caching:** Serialization for production performance

#### Quick Start

```php
use Zerotoprod\WebFramework\Router;

// Create router for this request with context
$routes = Router::for($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER)
    ->get('/users', [UserController::class, 'index'])
    ->get('/users/{id}', [UserController::class, 'show'])
    ->post('/users', [UserController::class, 'create']);

// Dispatch request
$routes->dispatch();
```

#### Supported HTTP Methods

All HTTP method helpers return the `Router` instance for fluent chaining:

```php
$routes->get('/resource', $action);      // GET requests
$routes->post('/resource', $action);     // POST requests
$routes->put('/resource', $action);      // PUT requests
$routes->patch('/resource', $action);    // PATCH requests
$routes->delete('/resource', $action);   // DELETE requests
$routes->options('/resource', $action);  // OPTIONS requests
$routes->head('/resource', $action);     // HEAD requests
$routes->any('/resource', $action);      // All HTTP methods
```

Each method returns the `Router` instance, allowing you to chain additional configuration methods like `where()`, `middleware()`, and `name()`, or continue defining more routes.

##### Any Method Routes

The `any()` method registers a route for all standard HTTP methods (GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD):

```php
// Responds to all HTTP methods
$routes->any('/api/webhook', [WebhookController::class, 'handle']);

// With specific methods array
$routes->any('/api/data', [DataController::class, 'process'], ['GET', 'POST']);

// With constraints
$routes->any('/users/{id}', [UserController::class, 'process'])
    ->where('id', '\d+');

// With middleware (applies to all methods)
$routes->any('/admin/action', [AdminController::class, 'handle'])
    ->middleware(AuthMiddleware::class);

// With route naming (all methods share the same name)
$routes->any('/process', [ProcessController::class, 'run'])
    ->name('process.run');
```

**Custom method filtering:**
```php
// Only respond to GET and POST
$routes->any('/api/endpoint', $action, ['GET', 'POST']);

// Lowercase methods are automatically converted to uppercase
$routes->any('/api/resource', $action, ['get', 'post', 'put']);

// Invalid methods are silently ignored
$routes->any('/test', $action, ['GET', 'INVALID']);  // Only registers GET
```

**When called with `where()`, `middleware()`, or `name()`**, the configuration applies to all routes created by `any()`.

#### Action Types

Routes support three types of actions:

##### 1. Closures

```php
$routes->get('/hello', function ($params) {
    echo "Hello, World!";
});
```

##### 2. Controller Arrays

```php
class UserController {
    public function index($params) {
        echo "User list";
    }

    public function show($params) {
        echo "Show user: " . $params['id'];
    }
}

$routes->get('/users', [UserController::class, 'index']);
$routes->get('/users/{id}', [UserController::class, 'show']);
```

##### 3. Invokable Controllers

```php
class HomeController {
    public function __invoke($params) {
        echo "<h1>Welcome Home</h1>";
    }
}

$routes->get('/', HomeController::class);
```

#### RESTful Resource Routes

Quickly define standard CRUD routes with the `resource()` method:

```php
// Generate all 7 RESTful routes
$routes->resource('posts', PostController::class);

// Generates:
// GET    /posts           → PostController::index()    [posts.index]
// GET    /posts/create    → PostController::create()   [posts.create]
// POST   /posts           → PostController::store()    [posts.store]
// GET    /posts/{id}      → PostController::show()     [posts.show]
// GET    /posts/{id}/edit → PostController::edit()     [posts.edit]
// PUT    /posts/{id}      → PostController::update()   [posts.update]
// DELETE /posts/{id}      → PostController::destroy()  [posts.destroy]
```

**Limiting actions:**

```php
// Only include specific actions
$routes->resource('photos', PhotoController::class, ['only' => ['index', 'show']]);

// Exclude specific actions
$routes->resource('users', UserController::class, ['except' => ['destroy']]);
```

**Named routes:**
All resource routes are automatically named using the pattern `{resource}.{action}`:

```php
$routes->resource('users', UserController::class);

// Generate URLs
$url = $routes->route('users.show', ['id' => 123]); // /users/123
$url = $routes->route('users.edit', ['id' => 456]); // /users/456/edit
```

#### Dynamic Routes with Parameters

##### Basic Parameters

```php
$routes->get('/users/{id}', function ($params) {
    echo "User ID: " . $params['id'];
    // GET /users/123 → params = ['id' => '123']
});

$routes->get('/posts/{slug}', function ($params) {
    echo "Post: " . $params['slug'];
    // GET /posts/hello-world → params = ['slug' => 'hello-world']
});
```

##### Multiple Parameters

```php
$routes->get('/users/{userId}/posts/{postId}', function ($params) {
    $userId = $params['userId'];
    $postId = $params['postId'];
    echo "User $userId, Post $postId";
    // GET /users/456/posts/789 → params = ['userId' => '456', 'postId' => '789']
});
```

#### Inline Constraints

Define parameter validation rules directly in the route pattern:

```php
// Numeric ID only
$routes->get('/users/{id:\d+}', function ($params) {
    echo "User ID: " . $params['id'];
    // Matches: /users/123
    // Doesn't match: /users/abc
});

// Alphanumeric slug
$routes->get('/posts/{slug:[a-z0-9-]+}', function ($params) {
    echo "Post: " . $params['slug'];
    // Matches: /posts/hello-world-123
    // Doesn't match: /posts/Hello_World
});

// UUID format
$routes->get('/items/{uuid:[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}}',
    function ($params) {
        echo "Item: " . $params['uuid'];
    }
);
```

#### Fluent Where Constraints

Use the `where()` method for cleaner constraint definitions:

```php
// Single constraint
$routes->get('/users/{id}', [UserController::class, 'show'])
    ->where('id', '\d+');

// Multiple constraints (array)
$routes->get('/posts/{year}/{month}', [PostController::class, 'archive'])
    ->where([
        'year' => '\d{4}',
        'month' => '\d{2}'
    ]);

// Chained constraints
$routes->get('/blog/{category}/{slug}', [BlogController::class, 'show'])
    ->where('category', '[a-z]+')
    ->where('slug', '[a-z0-9-]+');
```

#### Optional Parameters

Make parameters optional using the `?` suffix:

```php
// Optional parameter
$routes->get('/search/{query?}', function ($params) {
    $query = $params['query'] ?? 'default';
    echo "Searching for: $query";
    // Matches: /search → params = []
    // Matches: /search/php → params = ['query' => 'php']
});

// Multiple optional parameters
$routes->get('/blog/{year?}/{month?}', function ($params) {
    $year = $params['year'] ?? date('Y');
    $month = $params['month'] ?? date('m');
    echo "Archive: $year-$month";
});

// Optional with constraint
$routes->get('/users/{id:\d+?}', function ($params) {
    // Optional numeric ID
});

// Optional with where()
$routes->get('/posts/{page?}', [PostController::class, 'index'])
    ->where('page', '\d+');
```

#### Route Naming

Name routes for URL generation:

```php
$routes->get('/users/{id}', [UserController::class, 'show'])
    ->name('users.show');

$routes->post('/users', [UserController::class, 'create'])
    ->name('users.create');

// Generate URLs from named routes
$url = $routes->route('users.show', ['id' => 123]);
// Returns: /users/123
```

Route names enable URL generation while keeping route definitions centralized.

#### Route Groups

Organize routes with shared attributes using `prefix()` and `group()`:

##### Basic Prefix Groups

```php
// Apply prefix to multiple routes
$routes->prefix('admin')
    ->group(function ($r) {
        $r->get('/users', [AdminController::class, 'users']);     // /admin/users
        $r->get('/posts', [AdminController::class, 'posts']);     // /admin/posts
        $r->get('/settings', [AdminController::class, 'settings']); // /admin/settings
    });
```

##### Middleware Groups

```php
// Apply middleware to multiple routes
$routes->middleware(AuthMiddleware::class)
    ->group(function ($r) {
        $r->get('/dashboard', [DashboardController::class, 'index']);
        $r->get('/profile', [ProfileController::class, 'show']);
    });
```

##### Combined Prefix and Middleware

```php
// Apply both prefix and middleware
$routes->prefix('api')
    ->middleware([AuthMiddleware::class, RateLimitMiddleware::class])
    ->group(function ($r) {
        $r->get('/users', [ApiController::class, 'users']);
        $r->post('/users', [ApiController::class, 'createUser']);
    });
```

##### Nested Groups

Groups can be nested to create hierarchical route structures:

```php
// Nested prefix stacking
$routes->prefix('api')
    ->group(function ($r) {
        $r->prefix('v1')
            ->group(function ($r) {
                $r->get('/users', [ApiV1Controller::class, 'users']); // /api/v1/users
                $r->get('/posts', [ApiV1Controller::class, 'posts']); // /api/v1/posts
            });

        $r->prefix('v2')
            ->group(function ($r) {
                $r->get('/users', [ApiV2Controller::class, 'users']); // /api/v2/users
            });
    });

// Nested middleware stacking
$routes->middleware(LoggingMiddleware::class)
    ->group(function ($r) {
        $r->middleware(AuthMiddleware::class)
            ->group(function ($r) {
                // Both LoggingMiddleware and AuthMiddleware apply
                $r->get('/admin', [AdminController::class, 'index']);
            });
    });
```

**How groups work:**
- `prefix()` sets the prefix for the next `group()` call
- `middleware()` sets middleware for the next `group()` call
- Nested groups stack both prefixes and middleware
- Groups automatically clean up their state after execution

#### Additional Arguments (Dependency Injection)

Pass dependencies to all route handlers and middleware via Router::for():

```php
$database = new Database();
$logger = new Logger();

// Dependencies passed as additional arguments to Router::for()
$routes = Router::for('GET', '/users', $_SERVER, $database, $logger)
    ->get('/users', function ($params, $server, $db, $log) {
        $log->info('Fetching users');
        $users = $db->query('SELECT * FROM users');
        echo json_encode($users);
    });

$routes->dispatch();
```

**With controllers:**

```php
class UserController {
    public function index($params, $server, $db, $logger) {
        $logger->info('UserController::index called');
        return $db->fetchAll('users');
    }
}

$routes = Router::for('GET', '/api/users', $_SERVER, $database, $logger)
    ->get('/api/users', [UserController::class, 'index']);

$routes->dispatch();
```

#### 404 Fallback Handler

Define a fallback handler for unmatched routes:

```php
$routes = Router::for($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'])
    ->get('/', 'Home Page')
    ->get('/about', 'About Us')
    ->fallback(function ($params) {
        http_response_code(404);
        echo '404 - Page Not Found';
    });

$routes->dispatch();
```

**Fallback with controller:**

```php
class NotFoundController {
    public function __invoke($params) {
        http_response_code(404);
        include 'views/404.php';
    }
}

$routes = Router::for($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'])
    ->get('/', 'Home')
    ->fallback(NotFoundController::class);

$routes->dispatch();
```

**Fallback with dependencies:**

```php
$logger = new Logger();

$routes = Router::for($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER, $logger)
    ->fallback(function ($params, $server, $log) {
        $log->warning('404: ' . $server['REQUEST_URI']);
        echo '404 - Not Found';
    });

$routes->dispatch();
```

#### Middleware

##### Overview

Middleware provides a convenient mechanism for inspecting and filtering HTTP requests entering your routes. Middleware executes before route actions, making it perfect for authentication, logging, CORS, rate limiting, and more.

Middleware receives a `$next` callable followed by any arguments passed to `Router::for()`, allowing you to pass custom context objects, dependencies, or the `$_SERVER` superglobal.

##### Quick Start

```php
use Zerotoprod\WebFramework\Router;

$routes = Router::for($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER)
    ->globalMiddleware(function ($next, $server) {
        // Pre-action logic
        error_log("Request: {$server['REQUEST_METHOD']} {$server['REQUEST_URI']}");

        // Continue to next middleware or action
        $next();

        // Post-action logic (optional)
        error_log("Request completed");
    })
    ->get('/users', [UserController::class, 'index']);

$routes->dispatch();
```

##### Middleware Types

The router supports two middleware types:

1. **PSR-15 Middleware** (implements `Psr\Http\Server\MiddlewareInterface`)
2. **Variadic Middleware** (legacy callable format)

Both types can be used interchangeably and mixed freely.

##### Middleware Signature

**Variadic Middleware (Legacy):**

```php
function ($next, ...$context) {
    // $next is a closure to continue the middleware chain
    // $context contains all arguments passed to Router::for()
}
```

**PSR-15 Middleware (Recommended):**

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MyMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Pre-action logic

        // Continue to next middleware/action
        $response = $handler->handle($request);

        // Post-action logic
        return $response;
    }
}
```

**More explicitly**:
```php
function ($next, $server, $db = null, $logger = null) {
    // Middleware declares exactly what it expects
    // $server would be first context arg from Router::for()
    // $db would be second context arg (optional)
    // $logger would be third context arg (optional)
}
```

**Access to context arguments:**
- Context args are passed to `Router::for($method, $uri, ...$context)`
- First arg is typically `$_SERVER` (by convention)
- Additional args can be any dependencies (database, logger, etc.)
- Middleware declares what it needs via function parameters

##### Global Middleware

Register middleware that applies to all routes:

```php
// Single middleware
$routes->globalMiddleware(LoggingMiddleware::class);

// Multiple middleware (executes in order)
$routes->globalMiddleware([
    AuthenticationMiddleware::class,
    CorsMiddleware::class,
    LoggingMiddleware::class
]);

// Chain middleware registration
$routes->globalMiddleware(AuthMiddleware::class)
       ->globalMiddleware(LogMiddleware::class);
```

##### Per-Route Middleware

Add middleware to specific routes:

```php
// Single middleware
$routes->get('/admin', [AdminController::class, 'index'])
    ->middleware(AdminAuthMiddleware::class);

// Multiple middleware
$routes->post('/users', [UserController::class, 'store'])
    ->middleware([
        ValidateInputMiddleware::class,
        RateLimitMiddleware::class
    ]);

// Chain with other route methods
$routes->get('/profile/{id}', [ProfileController::class, 'show'])
    ->where('id', '\d+')
    ->middleware(AuthMiddleware::class)
    ->name('profile.show');
```

##### Creating Middleware Classes

**Invokable Class:**

```php
class AuthenticationMiddleware
{
    public function __invoke($next, $server)
    {
        // Pre-action: Check authentication
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return; // Don't call $next() - halt execution
        }

        // Pass control to next middleware or action
        $next();

        // Post-action: Optional cleanup or logging
        error_log("Request completed by user: {$_SESSION['user_id']}");
    }
}

// Usage
$routes->dispatch();
```

**Closure Middleware:**

```php
$routes->globalMiddleware(function ($next, $server) {
    $start = microtime(true);

    // Continue to action
    $next();

    // Log execution time
    $duration = microtime(true) - $start;
    error_log("{$server['REQUEST_METHOD']} {$server['REQUEST_URI']} - {$duration}s");
});

$routes->get('/users', [UserController::class, 'index']);

$routes->dispatch();
```

##### Execution Order

Middleware executes in this order:

1. **Global middleware** (in registration order)
2. **Route-specific middleware** (in registration order)
3. **Route action**
4. **Route-specific middleware post-processing** (in reverse order)
5. **Global middleware post-processing** (in reverse order)

```php
$routes = Router::for('GET', '/test')
    ->globalMiddleware(function ($next) {
        echo "1. Global before\n";
        $next();
        echo "6. Global after\n";
    })
    ->get('/test', function () {
        echo "4. Action\n";
    })
    ->middleware(function ($next) {
        echo "2. Route before\n";
        $next();
        echo "5. Route after\n";
    });

$routes->dispatch();

// Output: 1. Global before → 2. Route before → 4. Action → 5. Route after → 6. Global after
```

##### Halting Execution

Middleware can stop request processing by not calling `$next()`:

```php
class RateLimitMiddleware
{
    public function __invoke($next, $server)
    {
        $ip = $server['REMOTE_ADDR'] ?? 'unknown';

        if ($this->isRateLimited($ip)) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many requests']);
            return; // Halt - action will not execute
        }

        $next(); // Continue processing
    }
}
```

##### Practical Examples

**Authentication:**

```php
class AuthMiddleware
{
    public function __invoke($next, $server)
    {
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        $next();
    }
}
```

**CORS:**

```php
class CorsMiddleware
{
    public function __invoke($next, $server)
    {
        // Continue to action first
        $next();

        // Add CORS headers after action executes
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

        if (($server['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
        }
    }
}
```

**Logging:**

```php
class LoggingMiddleware
{
    public function __invoke($next, $server)
    {
        $method = $server['REQUEST_METHOD'] ?? 'UNKNOWN';
        $uri = $server['REQUEST_URI'] ?? '/';
        $ip = $server['REMOTE_ADDR'] ?? 'unknown';

        $start = microtime(true);

        $next();

        $duration = microtime(true) - $start;
        error_log("$method $uri from $ip - {$duration}s");
    }
}
```

**With Multiple Dependencies:**

```php
class UserMiddleware
{
    public function __invoke($next, $server, $db, $logger)
    {
        // Access all dependencies passed to Router::for()
        $logger->info("Request from: {$server['REMOTE_ADDR']}");

        $next();

        $logger->info("Request completed");
    }
}

$database = new Database();
$logger = new Logger();

$routes = Router::for('GET', '/users', $_SERVER, $database, $logger)
    ->globalMiddleware(UserMiddleware::class)
    ->get('/users', [UserController::class, 'index']);

$routes->dispatch();
```

##### Complete Example

```php
use Zerotoprod\WebFramework\Router;

// Create middleware classes
class AuthMiddleware
{
    public function __invoke($next, $server)
    {
        if (!isset($_SESSION['authenticated'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        $next();
    }
}

class LogMiddleware
{
    public function __invoke($next, $server)
    {
        error_log("Request: {$server['REQUEST_METHOD']} {$server['REQUEST_URI']}");
        $next();
    }
}

class AdminMiddleware
{
    public function __invoke($next, $server)
    {
        if ($_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        $next();
    }
}

// Define routes with middleware
$routes = Router::for($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER)
    // Global middleware for all routes
    ->globalMiddleware([
        LogMiddleware::class,
        AuthMiddleware::class
    ])

    // Public routes (global middleware still applies)
    ->get('/api/status', function() {
        echo 'OK';
    })

    // Protected admin routes
    ->get('/admin/users', [AdminController::class, 'users'])
        ->middleware(AdminMiddleware::class)
    ->post('/admin/users', [AdminController::class, 'createUser'])
        ->middleware([
            AdminMiddleware::class,
            ValidateInputMiddleware::class
        ])

    // Fallback
    ->fallback(function () {
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
    });

// Dispatch
$routes->dispatch();
```

##### Middleware and Caching

**⚠️ Important: Closure Limitation**

Just like routes, **middleware with closures cannot be cached** because PHP closures cannot be serialized.

✅ **Cacheable middleware:**
- Invokable classes: `AuthMiddleware::class`

❌ **Non-cacheable middleware:**
- Closures: `function ($next, $server) { ... }`

```php
// ✅ Can be cached (all middleware are class names)
$routes = Router::for('', '')
    ->globalMiddleware(AuthMiddleware::class)
    ->globalMiddleware(LoggingMiddleware::class)
    ->get('/users', [UserController::class, 'index'])
        ->middleware(RateLimitMiddleware::class);

if ($routes->isCacheable()) {
    file_put_contents('cache/routes.cache', $routes->compile());
}

// ❌ Cannot be cached (contains closure middleware)
$routes = Router::for('GET', '/users', $_SERVER)
    ->globalMiddleware(function ($next, $server) {
        // Closure cannot be serialized
        echo "Logging...";
        $next();
    })
    ->get('/users', [UserController::class, 'index']);

// Will throw RuntimeException
try {
    $routes->compile();
} catch (RuntimeException $e) {
    // "Cannot compile routes with closures..."
}
```

When loading cached routes, middleware is automatically restored:

```php
$compiled = file_get_contents('cache/routes.cache');
$routes = Router::for($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER)
    ->loadCompiled($compiled);

// Both global and per-route middleware are restored
$routes->dispatch();
```

#### Route Caching

Compile routes for production performance:

##### ⚠️ Important: Closure Limitation

**Routes and middleware with closures cannot be cached** because PHP closures cannot be serialized.

✅ **Cacheable route types:**
- Controller arrays: `[UserController::class, 'index']`
- Invokable classes: `UserController::class`
- Class-based middleware: `AuthMiddleware::class`

❌ **Non-cacheable route types:**
- Closures: `function ($params) { echo 'Hello'; }`
- Closure middleware: `function ($next, $server) { ... }`

##### Compiling Routes

```php
use Zerotoprod\WebFramework\Router;

// Define routes (using cacheable formats only)
$routes = Router::for('', '')
    ->get('/users', [UserController::class, 'index'])
    ->get('/users/{id:\d+}', [UserController::class, 'show'])
    ->post('/users', [UserController::class, 'create']);

// Compile and save
$compiled = $routes->compile();
file_put_contents('cache/routes.cache', $compiled);
```

##### Checking Cacheability

```php
use Zerotoprod\WebFramework\Router;

$routes = Router::for('', '')
    ->get('/users', [UserController::class, 'index'])
    ->get('/posts', function ($params) {
        echo 'Posts'; // Closure - not cacheable!
    });

if ($routes->isCacheable()) {
    file_put_contents('cache/routes.cache', $routes->compile());
} else {
    echo "Warning: Routes contain closures and cannot be cached\n";
}
```

##### Loading Cached Routes

```php
use Zerotoprod\WebFramework\Router;

// Load compiled routes from cache
$compiled = file_get_contents('cache/routes.cache');

$routes = Router::for($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER)
    ->loadCompiled($compiled);

// Dispatch immediately - no route definitions needed
$routes->dispatch();
```

##### Complete Caching Example

**build-cache.php** (run once to build cache):

```php
use Zerotoprod\WebFramework\Router;

$routes = Router::for('', '')
    ->get('/', 'Home')
    ->get('/users', [UserController::class, 'index'])
    ->get('/users/{id:\d+}', [UserController::class, 'show']);

if (!$routes->isCacheable()) {
    throw new Exception('Cannot build route cache: Routes contain closures.');
}

file_put_contents('cache/routes.cache', $routes->compile());
echo "✓ Route cache built successfully\n";
```

**index.php** (production entry point):

```php
use Zerotoprod\WebFramework\Router;

$compiled = file_get_contents('cache/routes.cache');
$routes = Router::for($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER)
    ->loadCompiled($compiled);

$routes->dispatch();
```

**Performance impact:**
- **Without caching:** Route definitions execute on every request (~100-500μs for 100 routes)
- **With caching:** Single deserialization operation (~20-50μs)
- **Speed improvement:** **5-20x faster** depending on route complexity

##### Automatic Caching (autoCache)

The `autoCache()` method provides environment-aware automatic route caching:

```php
use Zerotoprod\WebFramework\Router;

$routes = Router::for($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER)
    ->get('/users', [UserController::class, 'index'])
    ->get('/users/{id}', [UserController::class, 'show'])
    ->autoCache(__DIR__ . '/cache/routes.cache');

// Automatically caches on first request in production environment
// Automatically loads from cache on subsequent requests
$routes->dispatch();
```

**How it works:**
1. In **production** environment (`APP_ENV=production`):
   - First request: Builds routes and writes cache file
   - Subsequent requests: Loads routes from cache automatically
2. In **local/development** environments:
   - Cache is never written or read
   - Routes are built fresh on every request

**Custom environment configuration:**

```php
// Use custom environment variable
$routes->autoCache(
    'cache/routes.cache',
    'DEPLOY_ENV',           // Custom env var (default: APP_ENV)
    ['staging', 'production'] // Cache in these environments (default: ['production'])
);
```

**Benefits:**
- No manual cache management needed
- Automatically detects environment
- Creates cache directory if needed
- Safe for development (never caches in local)
- Production-optimized (automatic cache usage)

#### Method Chaining

Routes support fluent method chaining:

```php
use Zerotoprod\WebFramework\Router;

$routes = Router::for($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'])
    ->get('/', function() {
        echo 'Home Page';
    })
    ->get('/about', function() {
        echo 'About Us';
    })
    ->get('/users/{id:\d+}', [UserController::class, 'show'])
        ->name('users.show')
    ->post('/users', [UserController::class, 'create'])
        ->name('users.create')
    ->fallback(function ($params) {
        http_response_code(404);
        echo '404 - Not Found';
    });

$routes->dispatch();
```

**How it works:**
- All HTTP methods (`get()`, `post()`, etc.) return the `Router` instance for chaining
- Configuration methods (`where()`, `name()`, `middleware()`) also return the `Router` instance
- The router tracks the last defined route internally to apply configurations
- Routes are stored immediately when defined

This allows you to:
1. Define routes consecutively: `->get()->get()->post()`
2. Configure individual routes: `->get()->where()->name()`
3. Mix both patterns seamlessly in a single fluent chain

#### Route Matching Behavior

Routes use **exact** method and path matching:

```php
// Case-sensitive paths
$routes->get('/Users', $action);  // Only matches /Users
$routes->get('/users', $action);  // Only matches /users

// Method must match exactly
$routes->get('/data', $action);   // Only matches GET requests
$routes->post('/data', $action);  // Only matches POST requests
```

Query strings are automatically stripped:

```php
// Request: GET /search?q=test&page=2
$routes->get('/search', function ($params) {
    // This route matches!
    // Access query string via $_SERVER['QUERY_STRING']
});
```

#### Static vs Dynamic Route Priority

Static routes are checked first (O(1) hash lookup), then dynamic routes (O(n) regex matching):

```php
$routes->get('/users/create', function ($params) {
    echo 'Create new user form';  // This executes for /users/create
});

$routes->get('/users/{id}', function ($params) {
    echo 'Show user: ' . $params['id'];  // This executes for /users/123
});

// GET /users/create → "Create new user form" (static route wins)
// GET /users/123    → "Show user: 123" (dynamic route matches)
```

#### Performance Characteristics

The router uses a **three-level indexing system** for optimal performance:

**Level 1: Static Index (O(1))**
- Hash map: `method:path` → Route
- Perfect for exact path matches
- Most common case, fastest lookup

**Level 2: Prefix Index (O(1) + O(n))**
- Hash map: `method:prefix` → [Routes]
- Groups dynamic routes by common prefix
- Dramatically reduces routes to check for patterns like `/users/{id}`, `/posts/{slug}`

**Level 3: Method Index (O(n))**
- Hash map: `method` → [Routes]
- Fallback for complex dynamic routes without common prefixes
- Only checked if levels 1 and 2 don't match

**Performance Table:**

| Route Type | Example | Lookup | Performance |
|-----------|---------|--------|-------------|
| Static | `/users` | Level 1 | O(1) - Hash lookup |
| Dynamic with prefix | `/users/{id}` | Level 2 | O(1) + O(n small) |
| Dynamic no prefix | `/{tenant}/{resource}` | Level 3 | O(n) - Method filtered |

**Dispatch Order:**
1. **Static index** (O(1)) - Exact matches like `/users`, `/about`
2. **Prefix index** (O(1) + O(n small)) - Patterns like `/users/{id}`, `/api/posts/{slug}`
3. **Method index** (O(n)) - Complex patterns, routes without common prefixes
4. **Fallback handler** - If no route matches

**Best Practices:**
- Use static routes for hot paths (dashboards, landing pages)
- Group related dynamic routes with common prefixes (`/api/*`, `/admin/*`)
- Keep total routes under 500 for optimal prefix indexing
- Cache routes in production for best performance

#### Complete Example

```php
use Zerotoprod\WebFramework\Router;

class UserController {
    public function index($params) {
        echo json_encode(['users' => ['Alice', 'Bob']]);
    }

    public function show($params, $server, $db) {
        $user = $db->find('users', $params['id']);
        echo json_encode($user);
    }

    public function create($params, $server, $db) {
        $userId = $db->insert('users', $_POST);
        echo json_encode(['id' => $userId]);
    }
}

// Initialize database
$database = new Database();

// Define routes with context
$routes = Router::for($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER, $database)
    ->get('/', 'Welcome to the API')
    ->get('/users', [UserController::class, 'index'])
        ->name('users.index')
    ->get('/users/{id:\d+}', [UserController::class, 'show'])
        ->name('users.show')
    ->post('/users', [UserController::class, 'create'])
        ->name('users.create')
    ->get('/posts/{year}/{month?}', function ($params) {
        $year = $params['year'];
        $month = $params['month'] ?? 'all';
        echo "Archive: $year/$month";
    })
        ->where([
            'year' => '\d{4}',
            'month' => '\d{2}'
        ])
    ->fallback(function ($params) {
        http_response_code(404);
        echo json_encode(['error' => '404 Not Found']);
    });

// Dispatch
$routes->dispatch();
```

## Contributing

Contributions, issues, and feature requests are welcome!
Feel free to check the [issues](https://github.com/zero-to-prod/web-framework/issues) page if you want to contribute.

1. Fork the repository.
2. Create a new branch (`git checkout -b feature-branch`).
3. Commit changes (`git commit -m 'Add some feature'`).
4. Push to the branch (`git push origin feature-branch`).
5. Create a new Pull Request.
