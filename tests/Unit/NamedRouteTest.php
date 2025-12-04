<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zerotoprod\WebFramework\Router;

class NamedRouteTest extends TestCase
{
    /** @test */
    public function route_generates_url_with_required_parameters()
    {
        $router = Router::create()
            ->get('/users/{id}', ['Controller', 'show'])
            ->name('users.show');

        $url = $router->route('users.show', ['id' => 123]);

        $this->assertEquals('/users/123', $url);
    }

    /** @test */
    public function route_generates_url_with_multiple_parameters()
    {
        $router = Router::create()
            ->get('/posts/{year}/{slug}', ['Controller', 'show'])
            ->name('posts.show');

        $url = $router->route('posts.show', ['year' => 2025, 'slug' => 'hello']);

        $this->assertEquals('/posts/2025/hello', $url);
    }

    /** @test */
    public function route_generates_url_with_optional_parameters_provided()
    {
        $router = Router::create()
            ->get('/posts/{year}/{slug?}', ['Controller', 'show'])
            ->name('posts.show');

        $url = $router->route('posts.show', ['year' => 2025, 'slug' => 'hello']);

        $this->assertEquals('/posts/2025/hello', $url);
    }

    /** @test */
    public function route_generates_url_with_optional_parameters_omitted()
    {
        $router = Router::create()
            ->get('/posts/{year}/{slug?}', ['Controller', 'show'])
            ->name('posts.show');

        $url = $router->route('posts.show', ['year' => 2025]);

        $this->assertEquals('/posts/2025', $url);
    }

    /** @test */
    public function route_throws_exception_for_missing_required_parameter()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing required parameter: id');

        $router = Router::create()
            ->get('/users/{id}', ['Controller', 'show'])
            ->name('users.show');

        $router->route('users.show', []);
    }

    /** @test */
    public function route_throws_exception_for_nonexistent_route_name()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Route not found: nonexistent');

        $router = Router::create()
            ->get('/users/{id}', ['Controller', 'show'])
            ->name('users.show');

        $router->route('nonexistent', ['id' => 123]);
    }

    /** @test */
    public function route_validates_parameter_against_constraints()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("does not match constraint");

        $router = Router::create()
            ->get('/users/{id}', ['Controller', 'show'])
            ->where('id', '\d+')
            ->name('users.show');

        $router->route('users.show', ['id' => 'abc']);
    }

    /** @test */
    public function route_allows_parameter_matching_constraint()
    {
        $router = Router::create()
            ->get('/users/{id}', ['Controller', 'show'])
            ->where('id', '\d+')
            ->name('users.show');

        $url = $router->route('users.show', ['id' => '123']);

        $this->assertEquals('/users/123', $url);
    }

    /** @test */
    public function route_works_with_static_routes()
    {
        $router = Router::create()
            ->get('/about', ['Controller', 'about'])
            ->name('about');

        $url = $router->route('about');

        $this->assertEquals('/about', $url);
    }

    /** @test */
    public function route_works_with_resource_routes()
    {
        $router = Router::create()
            ->resource('users', 'UserController');

        $url = $router->route('users.show', ['id' => 123]);

        $this->assertEquals('/users/123', $url);
    }

    /** @test */
    public function route_works_with_grouped_routes()
    {
        $router = Router::create()
            ->prefix('admin')
            ->group(function ($r) {
                $r->get('/users/{id}', ['Controller', 'show'])->name('admin.users.show');
            });

        $url = $router->route('admin.users.show', ['id' => 123]);

        $this->assertEquals('/admin/users/123', $url);
    }

    /** @test */
    public function named_routes_survive_caching()
    {
        $router1 = Router::create()
            ->get('/users/{id}', ['Controller', 'show'])
            ->name('users.show');

        $compiled = $router1->compile();
        $router2 = Router::create()->loadCompiled($compiled);

        $url = $router2->route('users.show', ['id' => 123]);

        $this->assertEquals('/users/123', $url);
    }

    /** @test */
    public function multiple_named_routes_work()
    {
        $router = Router::create()
            ->get('/users/{id}', ['Controller', 'showUser'])
            ->name('users.show')
            ->get('/posts/{id}', ['Controller', 'showPost'])
            ->name('posts.show');

        $userUrl = $router->route('users.show', ['id' => 123]);
        $postUrl = $router->route('posts.show', ['id' => 456]);

        $this->assertEquals('/users/123', $userUrl);
        $this->assertEquals('/posts/456', $postUrl);
    }

    /** @test */
    public function route_with_multiple_constraints()
    {
        $router = Router::create()
            ->get('/posts/{year}/{slug}', ['Controller', 'show'])
            ->where(['year' => '\d{4}', 'slug' => '[a-z\-]+'])
            ->name('posts.show');

        $url = $router->route('posts.show', ['year' => '2025', 'slug' => 'hello-world']);

        $this->assertEquals('/posts/2025/hello-world', $url);
    }
}
