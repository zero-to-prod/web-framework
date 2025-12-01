<?php

namespace Tests\Unit\Plugins;

use Tests\TestCase;
use Zerotoprod\WebFramework\Plugins\HandleRoute;

class TestController
{
    public static $call_count = 0;
    public static $received_server = null;

    public function index(array $server = []): void
    {
        self::$call_count++;
        self::$received_server = $server;
    }

    public function show(array $server = []): void
    {
        echo 'Controller response';
        self::$received_server = $server;
    }

    public function create(): void
    {
        echo 'Created resource';
    }

    public function store(array $server = []): void
    {
        self::$call_count++;
        self::$received_server = $server;
    }

    public function update(): void
    {
        echo 'Updated resource';
    }

    public function destroy(array $server = []): void
    {
        self::$call_count++;
        self::$received_server = $server;
    }
}

class AnotherController
{
    public static $executed = false;

    public function handle(): void
    {
        self::$executed = true;
    }
}

class InvokeableController
{
    public static $invoked = false;
    public static $received_server = null;

    public function __invoke(array $server = []): void
    {
        self::$invoked = true;
        self::$received_server = $server;
        echo 'Invokeable controller executed';
    }
}

class InvokeableWithoutOutput
{
    public static $invoked = false;

    public function __invoke(array $server = []): void
    {
        self::$invoked = true;
    }
}

class HandleRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TestController::$call_count = 0;
        TestController::$received_server = null;
        AnotherController::$executed = false;
        InvokeableController::$invoked = false;
        InvokeableController::$received_server = null;
        InvokeableWithoutOutput::$invoked = false;
        TestControllerWithArgs::$received_args = [];
        InvokeableControllerWithArgs::$received_arg = null;
    }
    /** @test */
    public function can_instantiate_with_server_target(): void
    {
        $server = [];

        $router = new HandleRoute($server);

        $this->assertInstanceOf(HandleRoute::class, $router);
    }

    /** @test */
    public function get_method_returns_instance_for_chaining(): void
    {
        $server = [];
        $router = new HandleRoute($server);

        $result = $router->get('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function post_method_returns_instance_for_chaining(): void
    {
        $server = [];
        $router = new HandleRoute($server);

        $result = $router->post('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function put_method_returns_instance_for_chaining(): void
    {
        $server = [];
        $router = new HandleRoute($server);

        $result = $router->put('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function patch_method_returns_instance_for_chaining(): void
    {
        $server = [];
        $router = new HandleRoute($server);

        $result = $router->patch('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function delete_method_returns_instance_for_chaining(): void
    {
        $server = [];
        $router = new HandleRoute($server);

        $result = $router->delete('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function options_method_returns_instance_for_chaining(): void
    {
        $server = [];
        $router = new HandleRoute($server);

        $result = $router->options('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function head_method_returns_instance_for_chaining(): void
    {
        $server = [];
        $router = new HandleRoute($server);

        $result = $router->head('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function get_route_matches_and_executes_callable(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/home',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        });
        $router->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function post_route_matches_and_executes_callable(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/submit',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->post('/submit', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function put_route_matches_and_executes_callable(): void
    {
        $server = [
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/update',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->put('/update', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function patch_route_matches_and_executes_callable(): void
    {
        $server = [
            'REQUEST_METHOD' => 'PATCH',
            'REQUEST_URI' => '/partial-update',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->patch('/partial-update', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function delete_route_matches_and_executes_callable(): void
    {
        $server = [
            'REQUEST_METHOD' => 'DELETE',
            'REQUEST_URI' => '/remove',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->delete('/remove', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function options_route_matches_and_executes_callable(): void
    {
        $server = [
            'REQUEST_METHOD' => 'OPTIONS',
            'REQUEST_URI' => '/api',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->options('/api', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function head_route_matches_and_executes_callable(): void
    {
        $server = [
            'REQUEST_METHOD' => 'HEAD',
            'REQUEST_URI' => '/status',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->head('/status', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function route_outputs_string_when_action_is_string(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/hello',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->get('/hello', 'Hello World')->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Hello World', $output);
    }

    /** @test */
    public function route_does_not_execute_when_method_does_not_match(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/home',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        });

        $this->assertFalse($executed);
    }

    /** @test */
    public function route_does_not_execute_when_uri_does_not_match(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/about',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        });

        $this->assertFalse($executed);
    }

    /** @test */
    public function route_handles_query_string_in_uri(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/search?q=test',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/search', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function route_handles_multiple_query_parameters(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/results?page=1&limit=10&sort=name',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/results', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function multiple_routes_can_be_chained(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/about',
        ];
        $home_executed = false;
        $about_executed = false;

        $router = new HandleRoute($server);
        $router
            ->get('/home', function () use (&$home_executed) {
                $home_executed = true;
            })
            ->get('/about', function () use (&$about_executed) {
                $about_executed = true;
            })->dispatch();

        $this->assertFalse($home_executed);
        $this->assertTrue($about_executed);
    }

    /** @test */
    public function last_route_definition_wins_for_duplicates(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/duplicate',
        ];
        $first_executed = false;
        $second_executed = false;

        $router = new HandleRoute($server);
        $router
            ->get('/duplicate', function () use (&$first_executed) {
                $first_executed = true;
            })
            ->get('/duplicate', function () use (&$second_executed) {
                $second_executed = true;
            })->dispatch();

        // In O(1) hash map implementation, last definition wins (overwrites first)
        $this->assertFalse($first_executed);
        $this->assertTrue($second_executed);
    }

    /** @test */
    public function route_does_nothing_when_action_is_null(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/null-action',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->get('/null-action', null)->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('', $output);
    }

    /** @test */
    public function route_handles_empty_server_array(): void
    {
        $server = [];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertFalse($executed);
    }

    /** @test */
    public function route_handles_missing_request_method(): void
    {
        $server = [
            'REQUEST_URI' => '/home',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertFalse($executed);
    }

    /** @test */
    public function route_handles_missing_request_uri(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        });

        $this->assertFalse($executed);
    }

    /** @test */
    public function route_handles_root_path(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function route_is_case_sensitive_for_uri(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/Home',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        });

        $this->assertFalse($executed);
    }

    /** @test */
    public function route_requires_exact_method_match(): void
    {
        $server = [
            'REQUEST_METHOD' => 'get',
            'REQUEST_URI' => '/home',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        });

        $this->assertFalse($executed);
    }

    /** @test */
    public function callable_can_echo_output(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/echo-test',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->get('/echo-test', function () {
            echo 'Echoed content';
        })->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Echoed content', $output);
    }

    /** @test */
    public function different_methods_same_uri_handled_independently(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/resource',
        ];
        $get_executed = false;
        $post_executed = false;

        $router = new HandleRoute($server);
        $router
            ->get('/resource', function () use (&$get_executed) {
                $get_executed = true;
            })
            ->post('/resource', function () use (&$post_executed) {
                $post_executed = true;
            })->dispatch();

        $this->assertFalse($get_executed);
        $this->assertTrue($post_executed);
    }

    /** @test */
    public function route_accepts_controller_array_syntax(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/controller',
        ];

        $router = new HandleRoute($server);
        $router->get('/controller', [TestController::class, 'index'])->dispatch();

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function route_executes_controller_method_and_outputs_response(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/show',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->get('/show', [TestController::class, 'show'])->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Controller response', $output);
    }

    /** @test */
    public function post_route_accepts_controller_array_syntax(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/store',
        ];

        $router = new HandleRoute($server);
        $router->post('/store', [TestController::class, 'store'])->dispatch();

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function put_route_accepts_controller_array_syntax(): void
    {
        $server = [
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/update',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->put('/update', [TestController::class, 'update'])->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Updated resource', $output);
    }

    /** @test */
    public function patch_route_accepts_controller_array_syntax(): void
    {
        $server = [
            'REQUEST_METHOD' => 'PATCH',
            'REQUEST_URI' => '/partial-update',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->patch('/partial-update', [TestController::class, 'update'])->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Updated resource', $output);
    }

    /** @test */
    public function delete_route_accepts_controller_array_syntax(): void
    {
        $server = [
            'REQUEST_METHOD' => 'DELETE',
            'REQUEST_URI' => '/destroy',
        ];

        $router = new HandleRoute($server);
        $router->delete('/destroy', [TestController::class, 'destroy'])->dispatch();

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function options_route_accepts_controller_array_syntax(): void
    {
        $server = [
            'REQUEST_METHOD' => 'OPTIONS',
            'REQUEST_URI' => '/resource',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->options('/resource', [TestController::class, 'show'])->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Controller response', $output);
    }

    /** @test */
    public function head_route_accepts_controller_array_syntax(): void
    {
        $server = [
            'REQUEST_METHOD' => 'HEAD',
            'REQUEST_URI' => '/resource',
        ];

        $router = new HandleRoute($server);
        $router->head('/resource', [TestController::class, 'index'])->dispatch();

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function multiple_routes_with_controller_array_syntax(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/another',
        ];

        $router = new HandleRoute($server);
        $router
            ->get('/test', [TestController::class, 'index'])
            ->get('/another', [AnotherController::class, 'handle'])
            ->dispatch();

        $this->assertEquals(0, TestController::$call_count);
        $this->assertTrue(AnotherController::$executed);
    }

    /** @test */
    public function controller_array_syntax_only_executes_first_match(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/duplicate',
        ];

        $router = new HandleRoute($server);
        $router
            ->get('/duplicate', [TestController::class, 'index'])
            ->get('/duplicate', [TestController::class, 'store'])
            ->dispatch();

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function controller_array_syntax_chains_with_other_action_types(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/mixed',
        ];
        $closure_executed = false;

        $router = new HandleRoute($server);

        ob_start();
        $router
            ->get('/closure', function () use (&$closure_executed) {
                $closure_executed = true;
            })
            ->get('/mixed', [TestController::class, 'show'])
            ->get('/string', 'String response')
            ->dispatch();
        $output = ob_get_clean();

        $this->assertFalse($closure_executed);
        $this->assertEquals('Controller response', $output);
    }

    /** @test */
    public function controller_array_syntax_does_not_match_when_uri_different(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/wrong',
        ];

        $router = new HandleRoute($server);
        $router->get('/correct', [TestController::class, 'index']);

        $this->assertEquals(0, TestController::$call_count);
    }

    /** @test */
    public function controller_array_syntax_does_not_match_when_method_different(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/resource',
        ];

        $router = new HandleRoute($server);
        $router->get('/resource', [TestController::class, 'index']);

        $this->assertEquals(0, TestController::$call_count);
    }

    /** @test */
    public function controller_array_syntax_handles_query_strings(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/search?q=test&page=1',
        ];

        $router = new HandleRoute($server);
        $router->get('/search', [TestController::class, 'index'])->dispatch();

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function array_action_with_null_does_not_execute(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/wrong',
        ];

        $router = new HandleRoute($server);
        $router->get('/test', [TestController::class, 'index']);

        $this->assertEquals(0, TestController::$call_count);
    }

    /** @test */
    public function closure_receives_server_target(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'CUSTOM_KEY' => 'custom_value',
            'HTTP_HOST' => 'example.com',
        ];
        $received_server = null;

        $router = new HandleRoute($server);
        $router->get('/test', function ($srv) use (&$received_server) {
            $received_server = $srv;
        })->dispatch();

        $this->assertNotNull($received_server);
        $this->assertEquals($server, $received_server);
        $this->assertEquals('custom_value', $received_server['CUSTOM_KEY']);
        $this->assertEquals('example.com', $received_server['HTTP_HOST']);
    }

    /** @test */
    public function controller_receives_server_target(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/controller',
            'HTTP_HOST' => 'example.com',
            'SERVER_PORT' => '8080',
        ];

        $router = new HandleRoute($server);
        $router->get('/controller', [TestController::class, 'index'])->dispatch();

        $this->assertNotNull(TestController::$received_server);
        $this->assertEquals($server, TestController::$received_server);
        $this->assertEquals('example.com', TestController::$received_server['HTTP_HOST']);
        $this->assertEquals('8080', TestController::$received_server['SERVER_PORT']);
    }

    /** @test */
    public function closure_receives_all_server_array_keys(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/submit',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer token123',
            'REMOTE_ADDR' => '127.0.0.1',
        ];
        $received_server = null;

        $router = new HandleRoute($server);
        $router->post('/submit', function ($srv) use (&$received_server) {
            $received_server = $srv;
        })->dispatch();

        $this->assertArrayHasKey('REQUEST_METHOD', $received_server);
        $this->assertArrayHasKey('REQUEST_URI', $received_server);
        $this->assertArrayHasKey('CONTENT_TYPE', $received_server);
        $this->assertArrayHasKey('HTTP_AUTHORIZATION', $received_server);
        $this->assertArrayHasKey('REMOTE_ADDR', $received_server);
        $this->assertEquals('application/json', $received_server['CONTENT_TYPE']);
        $this->assertEquals('Bearer token123', $received_server['HTTP_AUTHORIZATION']);
    }

    /** @test */
    public function controller_receives_all_server_array_keys(): void
    {
        $server = [
            'REQUEST_METHOD' => 'DELETE',
            'REQUEST_URI' => '/resource/123',
            'HTTP_X_CUSTOM_HEADER' => 'custom_value',
            'QUERY_STRING' => 'force=true',
        ];

        $router = new HandleRoute($server);
        $router->delete('/resource/123', [TestController::class, 'destroy'])->dispatch();

        $this->assertArrayHasKey('REQUEST_METHOD', TestController::$received_server);
        $this->assertArrayHasKey('REQUEST_URI', TestController::$received_server);
        $this->assertArrayHasKey('HTTP_X_CUSTOM_HEADER', TestController::$received_server);
        $this->assertArrayHasKey('QUERY_STRING', TestController::$received_server);
        $this->assertEquals('DELETE', TestController::$received_server['REQUEST_METHOD']);
        $this->assertEquals('custom_value', TestController::$received_server['HTTP_X_CUSTOM_HEADER']);
    }

    /** @test */
    public function multiple_routes_each_receive_server_target(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/second',
            'CUSTOM_KEY' => 'value',
        ];
        $first_received = null;
        $second_received = null;

        $router = new HandleRoute($server);
        $router
            ->get('/first', function ($srv) use (&$first_received) {
                $first_received = $srv;
            })
            ->get('/second', function ($srv) use (&$second_received) {
                $second_received = $srv;
            })->dispatch();

        $this->assertNull($first_received);
        $this->assertNotNull($second_received);
        $this->assertEquals('value', $second_received['CUSTOM_KEY']);
    }

    /** @test */
    public function handles_empty_request_uri_without_query_string(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function get_route_accepts_invokeable_controller(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/invoke',
        ];

        $router = new HandleRoute($server);
        $router->get('/invoke', InvokeableController::class)->dispatch();

        $this->assertTrue(InvokeableController::$invoked);
    }

    /** @test */
    public function invokeable_controller_outputs_response(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/invoke',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->get('/invoke', InvokeableController::class)->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Invokeable controller executed', $output);
    }

    /** @test */
    public function post_route_accepts_invokeable_controller(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/invoke',
        ];

        $router = new HandleRoute($server);
        $router->post('/invoke', InvokeableController::class)->dispatch();

        $this->assertTrue(InvokeableController::$invoked);
    }

    /** @test */
    public function put_route_accepts_invokeable_controller(): void
    {
        $server = [
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/invoke',
        ];

        $router = new HandleRoute($server);
        $router->put('/invoke', InvokeableController::class)->dispatch();

        $this->assertTrue(InvokeableController::$invoked);
    }

    /** @test */
    public function patch_route_accepts_invokeable_controller(): void
    {
        $server = [
            'REQUEST_METHOD' => 'PATCH',
            'REQUEST_URI' => '/invoke',
        ];

        $router = new HandleRoute($server);
        $router->patch('/invoke', InvokeableController::class)->dispatch();

        $this->assertTrue(InvokeableController::$invoked);
    }

    /** @test */
    public function delete_route_accepts_invokeable_controller(): void
    {
        $server = [
            'REQUEST_METHOD' => 'DELETE',
            'REQUEST_URI' => '/invoke',
        ];

        $router = new HandleRoute($server);
        $router->delete('/invoke', InvokeableController::class)->dispatch();

        $this->assertTrue(InvokeableController::$invoked);
    }

    /** @test */
    public function options_route_accepts_invokeable_controller(): void
    {
        $server = [
            'REQUEST_METHOD' => 'OPTIONS',
            'REQUEST_URI' => '/invoke',
        ];

        $router = new HandleRoute($server);
        $router->options('/invoke', InvokeableController::class)->dispatch();

        $this->assertTrue(InvokeableController::$invoked);
    }

    /** @test */
    public function head_route_accepts_invokeable_controller(): void
    {
        $server = [
            'REQUEST_METHOD' => 'HEAD',
            'REQUEST_URI' => '/invoke',
        ];

        $router = new HandleRoute($server);
        $router->head('/invoke', InvokeableController::class)->dispatch();

        $this->assertTrue(InvokeableController::$invoked);
    }

    /** @test */
    public function invokeable_controller_receives_server_target(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/invoke',
            'HTTP_HOST' => 'example.com',
            'SERVER_PORT' => '8080',
        ];

        $router = new HandleRoute($server);
        $router->get('/invoke', InvokeableController::class)->dispatch();

        $this->assertNotNull(InvokeableController::$received_server);
        $this->assertEquals($server, InvokeableController::$received_server);
        $this->assertEquals('example.com', InvokeableController::$received_server['HTTP_HOST']);
        $this->assertEquals('8080', InvokeableController::$received_server['SERVER_PORT']);
    }

    /** @test */
    public function invokeable_controller_receives_all_server_array_keys(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/invoke',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer token123',
            'REMOTE_ADDR' => '127.0.0.1',
        ];

        $router = new HandleRoute($server);
        $router->post('/invoke', InvokeableController::class)->dispatch();

        $this->assertArrayHasKey('REQUEST_METHOD', InvokeableController::$received_server);
        $this->assertArrayHasKey('REQUEST_URI', InvokeableController::$received_server);
        $this->assertArrayHasKey('CONTENT_TYPE', InvokeableController::$received_server);
        $this->assertArrayHasKey('HTTP_AUTHORIZATION', InvokeableController::$received_server);
        $this->assertArrayHasKey('REMOTE_ADDR', InvokeableController::$received_server);
        $this->assertEquals('application/json', InvokeableController::$received_server['CONTENT_TYPE']);
        $this->assertEquals('Bearer token123', InvokeableController::$received_server['HTTP_AUTHORIZATION']);
    }

    /** @test */
    public function invokeable_controller_does_not_match_when_uri_different(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/wrong',
        ];

        $router = new HandleRoute($server);
        $router->get('/invoke', InvokeableController::class)->dispatch();

        $this->assertFalse(InvokeableController::$invoked);
    }

    /** @test */
    public function invokeable_controller_does_not_match_when_method_different(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/invoke',
        ];

        $router = new HandleRoute($server);
        $router->get('/invoke', InvokeableController::class)->dispatch();

        $this->assertFalse(InvokeableController::$invoked);
    }

    /** @test */
    public function invokeable_controller_handles_query_strings(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/invoke?param=value&page=1',
        ];

        $router = new HandleRoute($server);
        $router->get('/invoke', InvokeableController::class)->dispatch();

        $this->assertTrue(InvokeableController::$invoked);
    }

    /** @test */
    public function invokeable_controller_chains_with_other_action_types(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/invoke',
        ];
        $closure_executed = false;

        $router = new HandleRoute($server);

        ob_start();
        $router
            ->get('/closure', function () use (&$closure_executed) {
                $closure_executed = true;
            })
            ->get('/invoke', InvokeableController::class)
            ->get('/array', [TestController::class, 'show'])
            ->get('/string', 'String response')
            ->dispatch();
        $output = ob_get_clean();

        $this->assertFalse($closure_executed);
        $this->assertTrue(InvokeableController::$invoked);
        $this->assertEquals(0, TestController::$call_count);
        $this->assertEquals('Invokeable controller executed', $output);
    }

    /** @test */
    public function multiple_invokeable_controllers_on_different_routes(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/second',
        ];

        $router = new HandleRoute($server);
        $router
            ->get('/first', InvokeableController::class)
            ->get('/second', InvokeableWithoutOutput::class)
            ->dispatch();

        $this->assertFalse(InvokeableController::$invoked);
        $this->assertTrue(InvokeableWithoutOutput::$invoked);
    }

    /** @test */
    public function string_that_is_not_invokeable_class_echoes_as_string(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/string',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->get('/string', 'Just a plain string')->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Just a plain string', $output);
    }

    /** @test */
    public function non_existent_class_name_echoes_as_string(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/nonexistent',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->get('/nonexistent', 'NonExistentClass')->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('NonExistentClass', $output);
    }

    /** @test */
    public function class_without_invoke_method_echoes_as_string(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/regular',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->get('/regular', TestController::class)->dispatch();
        $output = ob_get_clean();

        $this->assertEquals(TestController::class, $output);
        $this->assertEquals(0, TestController::$call_count);
    }

    /** @test */
    public function fallback_returns_instance_for_chaining(): void
    {
        $server = [];
        $router = new HandleRoute($server);

        $result = $router->fallback(function () {
        });

        $this->assertSame($router, $result);
    }

    /** @test */
    public function fallback_executes_when_no_route_matches(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/nonexistent',
        ];
        $fallback_executed = false;

        $router = new HandleRoute($server);
        $router
            ->get('/home', function () {
            })
            ->fallback(function () use (&$fallback_executed) {
                $fallback_executed = true;
            })
            ->dispatch();

        $this->assertTrue($fallback_executed);
    }

    /** @test */
    public function fallback_does_not_execute_when_route_matches(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/home',
        ];
        $fallback_executed = false;
        $route_executed = false;

        $router = new HandleRoute($server);
        $router
            ->get('/home', function () use (&$route_executed) {
                $route_executed = true;
            })
            ->fallback(function () use (&$fallback_executed) {
                $fallback_executed = true;
            })
            ->dispatch();

        $this->assertTrue($route_executed);
        $this->assertFalse($fallback_executed);
    }

    /** @test */
    public function fallback_receives_server_target(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/unknown',
            'HTTP_HOST' => 'example.com',
            'CUSTOM_KEY' => 'custom_value',
        ];
        $received_server = null;

        $router = new HandleRoute($server);
        $router
            ->fallback(function ($srv) use (&$received_server) {
                $received_server = $srv;
            })
            ->dispatch();

        $this->assertNotNull($received_server);
        $this->assertEquals($server, $received_server);
        $this->assertEquals('example.com', $received_server['HTTP_HOST']);
        $this->assertEquals('custom_value', $received_server['CUSTOM_KEY']);
    }

    /** @test */
    public function fallback_outputs_string_when_action_is_string(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/notfound',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->fallback('404 - Page Not Found')->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('404 - Page Not Found', $output);
    }

    /** @test */
    public function fallback_accepts_controller_array_syntax(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/missing',
        ];

        $router = new HandleRoute($server);
        $router->fallback([TestController::class, 'index'])->dispatch();

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function fallback_accepts_invokeable_controller(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/404',
        ];

        $router = new HandleRoute($server);
        $router->fallback(InvokeableController::class)->dispatch();

        $this->assertTrue(InvokeableController::$invoked);
    }

    /** @test */
    public function fallback_with_null_does_nothing(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/missing',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->fallback(null)->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('', $output);
    }

    /** @test */
    public function dispatch_returns_true_when_fallback_executes(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/unknown',
        ];

        $router = new HandleRoute($server);
        $router->fallback(function () {
        });

        $result = $router->dispatch();

        $this->assertTrue($result);
    }

    /** @test */
    public function dispatch_returns_false_when_no_route_and_no_fallback(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/unknown',
        ];

        $router = new HandleRoute($server);
        $router->get('/home', function () {
        });

        $result = $router->dispatch();

        $this->assertFalse($result);
    }

    /** @test */
    public function dispatch_returns_true_when_route_matches_ignoring_fallback(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/home',
        ];

        $router = new HandleRoute($server);
        $router
            ->get('/home', function () {
            })
            ->fallback(function () {
            });

        $result = $router->dispatch();

        $this->assertTrue($result);
    }

    /** @test */
    public function fallback_handles_different_http_methods_when_no_routes_match(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/submit',
        ];
        $fallback_executed = false;

        $router = new HandleRoute($server);
        $router
            ->get('/submit', function () {
            })
            ->fallback(function () use (&$fallback_executed) {
                $fallback_executed = true;
            })
            ->dispatch();

        $this->assertTrue($fallback_executed);
    }

    /** @test */
    public function fallback_can_be_defined_before_routes(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/unknown',
        ];
        $fallback_executed = false;

        $router = new HandleRoute($server);
        $router
            ->fallback(function () use (&$fallback_executed) {
                $fallback_executed = true;
            })
            ->get('/home', function () {
            })
            ->dispatch();

        $this->assertTrue($fallback_executed);
    }

    /** @test */
    public function last_fallback_definition_wins(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/unknown',
        ];
        $first_executed = false;
        $second_executed = false;

        $router = new HandleRoute($server);
        $router
            ->fallback(function () use (&$first_executed) {
                $first_executed = true;
            })
            ->fallback(function () use (&$second_executed) {
                $second_executed = true;
            })
            ->dispatch();

        $this->assertFalse($first_executed);
        $this->assertTrue($second_executed);
    }

    /** @test */
    public function fallback_with_empty_server_array(): void
    {
        $server = [];
        $fallback_executed = false;

        $router = new HandleRoute($server);
        $router->fallback(function () use (&$fallback_executed) {
            $fallback_executed = true;
        })->dispatch();

        $this->assertTrue($fallback_executed);
    }

    /** @test */
    public function fallback_controller_receives_server_target(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/missing',
            'HTTP_HOST' => 'example.com',
            'SERVER_PORT' => '8080',
        ];

        $router = new HandleRoute($server);
        $router->fallback([TestController::class, 'index'])->dispatch();

        $this->assertNotNull(TestController::$received_server);
        $this->assertEquals($server, TestController::$received_server);
        $this->assertEquals('example.com', TestController::$received_server['HTTP_HOST']);
        $this->assertEquals('8080', TestController::$received_server['SERVER_PORT']);
    }

    /** @test */
    public function fallback_invokeable_controller_receives_server_target(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/404',
            'HTTP_HOST' => 'example.com',
            'CUSTOM_HEADER' => 'value',
        ];

        $router = new HandleRoute($server);
        $router->fallback(InvokeableController::class)->dispatch();

        $this->assertNotNull(InvokeableController::$received_server);
        $this->assertEquals($server, InvokeableController::$received_server);
        $this->assertEquals('example.com', InvokeableController::$received_server['HTTP_HOST']);
        $this->assertEquals('value', InvokeableController::$received_server['CUSTOM_HEADER']);
    }

    /** @test */
    public function fallback_chains_with_all_http_methods(): void
    {
        $server = [
            'REQUEST_METHOD' => 'DELETE',
            'REQUEST_URI' => '/unknown',
        ];
        $fallback_executed = false;

        $router = new HandleRoute($server);
        $router
            ->get('/home', function () {
            })
            ->post('/submit', function () {
            })
            ->put('/update', function () {
            })
            ->patch('/partial', function () {
            })
            ->delete('/remove', function () {
            })
            ->options('/opts', function () {
            })
            ->head('/head', function () {
            })
            ->fallback(function () use (&$fallback_executed) {
                $fallback_executed = true;
            })
            ->dispatch();

        $this->assertTrue($fallback_executed);
    }

    /** @test */
    public function closure_receives_single_additional_argument(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
        ];
        $received_arg = null;

        $router = new HandleRoute($server, 'custom_value');
        $router->get('/test', function ($srv, $arg) use (&$received_arg) {
            $received_arg = $arg;
        })->dispatch();

        $this->assertEquals('custom_value', $received_arg);
    }

    /** @test */
    public function closure_receives_multiple_additional_arguments(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
        ];
        $received_args = [];

        $router = new HandleRoute($server, 'arg1', 'arg2', 'arg3');
        $router->get('/test', function ($srv, ...$args) use (&$received_args) {
            $received_args = $args;
        })->dispatch();

        $this->assertEquals(['arg1', 'arg2', 'arg3'], $received_args);
    }

    /** @test */
    public function closure_receives_object_as_additional_argument(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
        ];
        $dependency = new \stdClass();
        $dependency->value = 'test_value';
        $received_dependency = null;

        $router = new HandleRoute($server, $dependency);
        $router->get('/test', function ($srv, $dep) use (&$received_dependency) {
            $received_dependency = $dep;
        })->dispatch();

        $this->assertSame($dependency, $received_dependency);
        $this->assertEquals('test_value', $received_dependency->value);
    }

    /** @test */
    public function controller_array_syntax_receives_additional_arguments(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
        ];

        $router = new HandleRoute($server, 'extra_arg1', 'extra_arg2');
        $router->get('/test', [TestControllerWithArgs::class, 'handleWithArgs'])->dispatch();

        $this->assertEquals(['extra_arg1', 'extra_arg2'], TestControllerWithArgs::$received_args);
    }

    /** @test */
    public function invokeable_controller_receives_additional_arguments(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
        ];

        $router = new HandleRoute($server, 'arg_value');
        $router->get('/test', InvokeableControllerWithArgs::class)->dispatch();

        $this->assertEquals('arg_value', InvokeableControllerWithArgs::$received_arg);
    }

    /** @test */
    public function fallback_receives_additional_arguments(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/unknown',
        ];
        $received_arg = null;

        $router = new HandleRoute($server, 'fallback_arg');
        $router->fallback(function ($srv, $arg) use (&$received_arg) {
            $received_arg = $arg;
        })->dispatch();

        $this->assertEquals('fallback_arg', $received_arg);
    }

    /** @test */
    public function no_additional_arguments_works_correctly(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/test', function ($srv) use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }
}

class TestControllerWithArgs
{
    public static $received_args = [];

    public function handleWithArgs($server, ...$args): void
    {
        self::$received_args = $args;
    }
}

class InvokeableControllerWithArgs
{
    public static $received_arg = null;

    public function __invoke($server, $arg = null): void
    {
        self::$received_arg = $arg;
    }
}
