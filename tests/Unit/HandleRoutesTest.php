<?php

namespace tests\Unit;

use Tests\TestCase;
use Zerotoprod\WebFramework\Plugins\HandleRoute;
use Zerotoprod\WebFramework\WebFramework;

class HandleRoutesTest extends TestCase
{
    /** @test */
    public function run_sets_env_variables_to_globals(): void
    {
        $env = ['env' => 'env'];
        $server = ['server' => 'server'];
        (new WebFramework())
            ->setEnvTarget($env)
            ->setServerTarget($server)
            ->handleRoutes(function ($env, $server) {
                $this->assertSame(['env' => 'env'], $env);
                $this->assertSame(['server' => 'server'], $server);
            });
    }

    /** @test */
    public function handle_routes_works_with_handle_route_plugin(): void
    {
        $env = [];
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test-route',
        ];
        $route_executed = false;

        (new WebFramework())
            ->setEnvTarget($env)
            ->setServerTarget($server)
            ->handleRoutes(function ($env, $server) use (&$route_executed) {
                (new HandleRoute($server))
                    ->get('/test-route', function () use (&$route_executed) {
                        $route_executed = true;
                    })->dispatch();
            });

        $this->assertTrue($route_executed);
    }

    /** @test */
    public function handle_routes_supports_multiple_routes_with_handle_route_plugin(): void
    {
        $env = [];
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/about',
        ];
        $home_executed = false;
        $about_executed = false;

        (new WebFramework())
            ->setEnvTarget($env)
            ->setServerTarget($server)
            ->handleRoutes(function ($env, $server) use (&$home_executed, &$about_executed) {
                (new HandleRoute($server))
                    ->get('/home', function () use (&$home_executed) {
                        $home_executed = true;
                    })
                    ->get('/about', function () use (&$about_executed) {
                        $about_executed = true;
                    })->dispatch();
            });

        $this->assertFalse($home_executed);
        $this->assertTrue($about_executed);
    }

    /** @test */
    public function handle_routes_supports_string_responses_with_handle_route_plugin(): void
    {
        $env = [];
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/hello',
        ];

        ob_start();
        (new WebFramework())
            ->setEnvTarget($env)
            ->setServerTarget($server)
            ->handleRoutes(function ($env, $server) {
                (new HandleRoute($server))
                    ->get('/hello', 'Hello World')->dispatch();
            });
        $output = ob_get_clean();

        $this->assertEquals('Hello World', $output);
    }

    /** @test */
    public function handle_routes_supports_different_http_methods_with_handle_route_plugin(): void
    {
        $env = [];
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/submit',
        ];
        $post_executed = false;

        (new WebFramework())
            ->setEnvTarget($env)
            ->setServerTarget($server)
            ->handleRoutes(function ($env, $server) use (&$post_executed) {
                (new HandleRoute($server))
                    ->get('/submit', function () {
                    })
                    ->post('/submit', function () use (&$post_executed) {
                        $post_executed = true;
                    })->dispatch();
            });

        $this->assertTrue($post_executed);
    }
}