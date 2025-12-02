<?php

namespace Tests\Unit\Plugins;

use Tests\TestCase;
use Zerotoprod\WebFramework\Plugins\HandleRoute;

class TestController
{
    public static $call_count = 0;
        public function index(): void
    {
        self::$call_count++;
            }

    public function show(): void
    {
        echo 'Controller response';
            }

    public function create(): void
    {
        echo 'Created resource';
    }

    public function store(): void
    {
        self::$call_count++;
            }

    public function update(): void
    {
        echo 'Updated resource';
    }

    public function destroy(): void
    {
        self::$call_count++;
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
        public function __invoke(): void
    {
        self::$invoked = true;
                echo 'Invokeable controller executed';
    }
}

class InvokeableWithoutOutput
{
    public static $invoked = false;

    public function __invoke(): void
    {
        self::$invoked = true;
    }
}

class UserController
{
    public function index(): void
    {
        echo 'User list';
    }
}

class DynamicControllerMultiParams
{
    public static $received_params = null;

    public function handle($params): void
    {
        self::$received_params = $params;
    }
}

class HandleRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TestController::$call_count = 0;
                AnotherController::$executed = false;
        InvokeableController::$invoked = false;
                InvokeableWithoutOutput::$invoked = false;
        TestControllerWithArgs::$received_args = [];
        InvokeableControllerWithArgs::$received_arg = null;
        DynamicController::$received_params = null;
        InvokeableDynamicController::$received_params = null;
        CacheTestController::$executed = false;
        DynamicControllerMultiParams::$received_params = null;
        RegexController::$received_params = null;
        InvokeableRegexController::$received_params = null;
    }
    /** @test */
    public function can_instantiate_with_method_and_uri(): void
    {
        $router = new HandleRoute('GET', '/');

        $this->assertInstanceOf(HandleRoute::class, $router);
    }

    /** @test */
    public function get_method_returns_instance_for_chaining(): void
    {
        $router = new HandleRoute('GET', '/test');

        $result = $router->get('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function post_method_returns_instance_for_chaining(): void
    {
        $router = new HandleRoute('POST', '/test');

        $result = $router->post('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function put_method_returns_instance_for_chaining(): void
    {
        $router = new HandleRoute('PUT', '/test');

        $result = $router->put('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function patch_method_returns_instance_for_chaining(): void
    {
        $router = new HandleRoute('PATCH', '/test');

        $result = $router->patch('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function delete_method_returns_instance_for_chaining(): void
    {
        $router = new HandleRoute('DELETE', '/test');

        $result = $router->delete('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function options_method_returns_instance_for_chaining(): void
    {
        $router = new HandleRoute('OPTIONS', '/test');

        $result = $router->options('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function head_method_returns_instance_for_chaining(): void
    {
        $router = new HandleRoute('HEAD', '/test');

        $result = $router->head('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function get_route_matches_and_executes_callable(): void
    {
        $executed = false;

        $router = new HandleRoute('GET', '/home');
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        });
        $router->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function post_route_matches_and_executes_callable(): void
    {
        $executed = false;

        $router = new HandleRoute('POST', '/submit');
        $router->post('/submit', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function put_route_matches_and_executes_callable(): void
    {
        $executed = false;

        $router = new HandleRoute('PUT', '/update');
        $router->put('/update', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function patch_route_matches_and_executes_callable(): void
    {
        $executed = false;

        $router = new HandleRoute('PATCH', '/partial-update');
        $router->patch('/partial-update', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function delete_route_matches_and_executes_callable(): void
    {
        $executed = false;

        $router = new HandleRoute('DELETE', '/remove');
        $router->delete('/remove', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function options_route_matches_and_executes_callable(): void
    {
        $executed = false;

        $router = new HandleRoute('OPTIONS', '/api');
        $router->options('/api', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function head_route_matches_and_executes_callable(): void
    {
        $executed = false;

        $router = new HandleRoute('HEAD', '/status');
        $router->head('/status', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function route_outputs_string_when_action_is_string(): void
    {
        $router = new HandleRoute('GET', '/hello');

        ob_start();
        $router->get('/hello', 'Hello World')->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Hello World', $output);
    }

    /** @test */
    public function route_does_not_execute_when_method_does_not_match(): void
    {
        $executed = false;

        $router = new HandleRoute('POST', '/home');
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        });

        $this->assertFalse($executed);
    }

    /** @test */
    public function route_does_not_execute_when_uri_does_not_match(): void
    {
        $executed = false;

        $router = new HandleRoute('GET', '/about');
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        });

        $this->assertFalse($executed);
    }

    /** @test */
    public function route_handles_query_string_in_uri(): void
    {
        $executed = false;

        $router = new HandleRoute('GET', '/search?q=test');
        $router->get('/search', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function route_handles_multiple_query_parameters(): void
    {
        $executed = false;

        $router = new HandleRoute('GET', '/results?page=1&limit=10&sort=name');
        $router->get('/results', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function multiple_routes_can_be_chained(): void
    {
        $home_executed = false;
        $about_executed = false;

        $router = new HandleRoute('GET', '/about');
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
        $first_executed = false;
        $second_executed = false;

        $router = new HandleRoute('GET', '/duplicate');
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
        $router = new HandleRoute('GET', '/null-action');

        ob_start();
        $router->get('/null-action', null)->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('', $output);
    }

    /** @test */
    public function route_handles_root_path(): void
    {
        $executed = false;

        $router = new HandleRoute('GET', '/');
        $router->get('/', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function route_is_case_sensitive_for_uri(): void
    {
        $executed = false;

        $router = new HandleRoute('GET', '/Home');
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        });

        $this->assertFalse($executed);
    }

    /** @test */
    public function route_requires_exact_method_match(): void
    {
        $executed = false;

        $router = new HandleRoute('get', '/home');
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        });

        $this->assertFalse($executed);
    }

    /** @test */
    public function callable_can_echo_output(): void
    {
        $router = new HandleRoute('GET', '/echo-test');

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
        $get_executed = false;
        $post_executed = false;

        $router = new HandleRoute('POST', '/resource');
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
        $router = new HandleRoute('GET', '/controller');
        $router->get('/controller', [TestController::class, 'index'])->dispatch();

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function route_executes_controller_method_and_outputs_response(): void
    {
        $router = new HandleRoute('GET', '/show');

        ob_start();
        $router->get('/show', [TestController::class, 'show'])->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Controller response', $output);
    }

    /** @test */
    public function post_route_accepts_controller_array_syntax(): void
    {
        $router = new HandleRoute('POST', '/store');
        $router->post('/store', [TestController::class, 'store'])->dispatch();

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function put_route_accepts_controller_array_syntax(): void
    {
        $router = new HandleRoute('PUT', '/update');

        ob_start();
        $router->put('/update', [TestController::class, 'update'])->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Updated resource', $output);
    }

    /** @test */
    public function patch_route_accepts_controller_array_syntax(): void
    {
        $router = new HandleRoute('PATCH', '/partial-update');

        ob_start();
        $router->patch('/partial-update', [TestController::class, 'update'])->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Updated resource', $output);
    }

    /** @test */
    public function delete_route_accepts_controller_array_syntax(): void
    {
        $router = new HandleRoute('DELETE', '/destroy');
        $router->delete('/destroy', [TestController::class, 'destroy'])->dispatch();

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function options_route_accepts_controller_array_syntax(): void
    {
        $router = new HandleRoute('OPTIONS', '/resource');

        ob_start();
        $router->options('/resource', [TestController::class, 'show'])->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Controller response', $output);
    }

    /** @test */
    public function head_route_accepts_controller_array_syntax(): void
    {

        $router = new HandleRoute('HEAD', '/resource');
        $router->head('/resource', [TestController::class, 'index'])->dispatch();

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function multiple_routes_with_controller_array_syntax(): void
    {
        $router = new HandleRoute('GET', '/another');
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
        $router = new HandleRoute('GET', '/duplicate');
        $router
            ->get('/duplicate', [TestController::class, 'index'])
            ->get('/duplicate', [TestController::class, 'store'])
            ->dispatch();

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function controller_array_syntax_chains_with_other_action_types(): void
    {
        $closure_executed = false;

        $router = new HandleRoute('GET', '/mixed');

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
        $router = new HandleRoute('GET', '/wrong');
        $router->get('/correct', [TestController::class, 'index']);

        $this->assertEquals(0, TestController::$call_count);
    }

    /** @test */
    public function controller_array_syntax_does_not_match_when_method_different(): void
    {
        $router = new HandleRoute('POST', '/resource');
        $router->get('/resource', [TestController::class, 'index']);

        $this->assertEquals(0, TestController::$call_count);
    }

    /** @test */
    public function controller_array_syntax_handles_query_strings(): void
    {
        $router = new HandleRoute('GET', '/search?q=test&page=1');
        $router->get('/search', [TestController::class, 'index'])->dispatch();

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function array_action_with_null_does_not_execute(): void
    {
        $router = new HandleRoute('GET', '/wrong');
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

        $router = new HandleRoute('GET', '/test', $server);
        $router->get('/test', function ($srv) use (&$received_server) {
            $received_server = $srv;
        })->dispatch();

        $this->assertNotNull($received_server);
        $this->assertEquals($server, $received_server);
        $this->assertEquals('custom_value', $received_server['CUSTOM_KEY']);
        $this->assertEquals('example.com', $received_server['HTTP_HOST']);
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

        $router = new HandleRoute('POST', '/submit', $server);
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
    public function multiple_routes_each_receive_server_target(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/second',
            'CUSTOM_KEY' => 'value',
        ];
        $first_received = null;
        $second_received = null;

        $router = new HandleRoute('GET', '/second', $server);
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
        $executed = false;

        $router = new HandleRoute('GET', '');
        $router->get('', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function get_route_accepts_invokeable_controller(): void
    {
        $router = new HandleRoute('GET', '/invoke');
        $router->get('/invoke', InvokeableController::class)->dispatch();

        $this->assertTrue(InvokeableController::$invoked);
    }

    /** @test */
    public function invokeable_controller_outputs_response(): void
    {

        $router = new HandleRoute('GET', '/invoke');

        ob_start();
        $router->get('/invoke', InvokeableController::class)->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Invokeable controller executed', $output);
    }

    /** @test */
    public function post_route_accepts_invokeable_controller(): void
    {
        $router = new HandleRoute('POST', '/invoke');
        $router->post('/invoke', InvokeableController::class)->dispatch();

        $this->assertTrue(InvokeableController::$invoked);
    }

    /** @test */
    public function put_route_accepts_invokeable_controller(): void
    {
        $router = new HandleRoute('PUT', '/invoke');
        $router->put('/invoke', InvokeableController::class)->dispatch();

        $this->assertTrue(InvokeableController::$invoked);
    }

    /** @test */
    public function patch_route_accepts_invokeable_controller(): void
    {
        $router = new HandleRoute('PATCH', '/invoke');
        $router->patch('/invoke', InvokeableController::class)->dispatch();

        $this->assertTrue(InvokeableController::$invoked);
    }

    /** @test */
    public function delete_route_accepts_invokeable_controller(): void
    {
        $router = new HandleRoute('DELETE', '/invoke');
        $router->delete('/invoke', InvokeableController::class)->dispatch();

        $this->assertTrue(InvokeableController::$invoked);
    }

    /** @test */
    public function options_route_accepts_invokeable_controller(): void
    {
        $router = new HandleRoute('OPTIONS', '/invoke');
        $router->options('/invoke', InvokeableController::class)->dispatch();

        $this->assertTrue(InvokeableController::$invoked);
    }

    /** @test */
    public function head_route_accepts_invokeable_controller(): void
    {
        $router = new HandleRoute('HEAD', '/invoke');
        $router->head('/invoke', InvokeableController::class)->dispatch();

        $this->assertTrue(InvokeableController::$invoked);
    }

    /** @test */
    public function invokeable_controller_does_not_match_when_uri_different(): void
    {
        $router = new HandleRoute('GET', '/wrong');
        $router->get('/invoke', InvokeableController::class)->dispatch();

        $this->assertFalse(InvokeableController::$invoked);
    }

    /** @test */
    public function invokeable_controller_does_not_match_when_method_different(): void
    {

        $router = new HandleRoute('POST', '/invoke');
        $router->get('/invoke', InvokeableController::class)->dispatch();

        $this->assertFalse(InvokeableController::$invoked);
    }

    /** @test */
    public function invokeable_controller_handles_query_strings(): void
    {
        $router = new HandleRoute('GET', '/invoke?param=value&page=1');
        $router->get('/invoke', InvokeableController::class)->dispatch();

        $this->assertTrue(InvokeableController::$invoked);
    }

    /** @test */
    public function invokeable_controller_chains_with_other_action_types(): void
    {
        $closure_executed = false;

        $router = new HandleRoute('GET', '/invoke');

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
        $router = new HandleRoute('GET', '/second');
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
        $router = new HandleRoute('GET', '/string');

        ob_start();
        $router->get('/string', 'Just a plain string')->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Just a plain string', $output);
    }

    /** @test */
    public function non_existent_class_name_echoes_as_string(): void
    {
        $router = new HandleRoute('GET', '/nonexistent');

        ob_start();
        $router->get('/nonexistent', 'NonExistentClass')->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('NonExistentClass', $output);
    }

    /** @test */
    public function class_without_invoke_method_echoes_as_string(): void
    {
        $router = new HandleRoute('GET', '/regular');

        ob_start();
        $router->get('/regular', TestController::class)->dispatch();
        $output = ob_get_clean();

        $this->assertEquals(TestController::class, $output);
        $this->assertEquals(0, TestController::$call_count);
    }

    /** @test */
    public function fallback_returns_instance_for_chaining(): void
    {
        $router = new HandleRoute('GET', '/');

        $result = $router->fallback(function () {
        });

        $this->assertSame($router, $result);
    }

    /** @test */
    public function fallback_executes_when_no_route_matches(): void
    {
        $fallback_executed = false;

        $router = new HandleRoute('GET', '/nonexistent');
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
        $fallback_executed = false;
        $route_executed = false;

        $router = new HandleRoute('GET', '/home');
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
    public function fallback_outputs_string_when_action_is_string(): void
    {
        $router = new HandleRoute('GET', '/notfound');

        ob_start();
        $router->fallback('404 - Page Not Found')->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('404 - Page Not Found', $output);
    }

    /** @test */
    public function fallback_accepts_controller_array_syntax(): void
    {
        $router = new HandleRoute('GET', '/missing');
        $router->fallback([TestController::class, 'index'])->dispatch();

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function fallback_accepts_invokeable_controller(): void
    {
        $router = new HandleRoute('GET', '/404');
        $router->fallback(InvokeableController::class)->dispatch();

        $this->assertTrue(InvokeableController::$invoked);
    }

    /** @test */
    public function fallback_with_null_does_nothing(): void
    {
        $router = new HandleRoute('GET', '/missing');

        ob_start();
        $router->fallback(null)->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('', $output);
    }

    /** @test */
    public function dispatch_returns_true_when_fallback_executes(): void
    {
        $router = new HandleRoute('GET', '/unknown');
        $router->fallback(function () {
        });

        $result = $router->dispatch();

        $this->assertTrue($result);
    }

    /** @test */
    public function dispatch_returns_false_when_no_route_and_no_fallback(): void
    {
        $router = new HandleRoute('GET', '/unknown');
        $router->get('/home', function () {
        });

        $result = $router->dispatch();

        $this->assertFalse($result);
    }

    /** @test */
    public function dispatch_returns_true_when_route_matches_ignoring_fallback(): void
    {
        $router = new HandleRoute('GET', '/home');
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
        $fallback_executed = false;

        $router = new HandleRoute('POST', '/submit');
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
        $fallback_executed = false;

        $router = new HandleRoute('GET', '/unknown');
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
        $first_executed = false;
        $second_executed = false;

        $router = new HandleRoute('GET', '/unknown');
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
        $fallback_executed = false;

        $router = new HandleRoute('', '');
        $router->fallback(function () use (&$fallback_executed) {
            $fallback_executed = true;
        })->dispatch();

        $this->assertTrue($fallback_executed);
    }


    /** @test */
    public function fallback_chains_with_all_http_methods(): void
    {
        $fallback_executed = false;

        $router = new HandleRoute('DELETE', '/unknown');
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
        $received_arg = null;

        $router = new HandleRoute('GET', '/test', 'custom_value');
        $router->get('/test', function ($arg) use (&$received_arg) {
            $received_arg = $arg;
        })->dispatch();

        $this->assertEquals('custom_value', $received_arg);
    }

    /** @test */
    public function closure_receives_multiple_additional_arguments(): void
    {
        $received_args = [];

        $router = new HandleRoute('GET', '/test', 'arg1', 'arg2', 'arg3');
        $router->get('/test', function (...$args) use (&$received_args) {
            $received_args = $args;
        })->dispatch();

        $this->assertEquals(['arg1', 'arg2', 'arg3'], $received_args);
    }

    /** @test */
    public function closure_receives_object_as_additional_argument(): void
    {
        $dependency = new \stdClass();
        $dependency->value = 'test_value';
        $received_dependency = null;

        $router = new HandleRoute('GET', '/test', $dependency);
        $router->get('/test', function ($dep) use (&$received_dependency) {
            $received_dependency = $dep;
        })->dispatch();

        $this->assertSame($dependency, $received_dependency);
        $this->assertEquals('test_value', $received_dependency->value);
    }

    /** @test */
    public function controller_array_syntax_receives_additional_arguments(): void
    {
        $router = new HandleRoute('GET', '/test', 'extra_arg1', 'extra_arg2');
        $router->get('/test', [TestControllerWithArgs::class, 'handleWithArgs'])->dispatch();

        $this->assertEquals(['extra_arg1', 'extra_arg2'], TestControllerWithArgs::$received_args);
    }

    /** @test */
    public function invokeable_controller_receives_additional_arguments(): void
    {
        $router = new HandleRoute('GET', '/test', 'arg_value');
        $router->get('/test', InvokeableControllerWithArgs::class)->dispatch();

        $this->assertEquals('arg_value', InvokeableControllerWithArgs::$received_arg);
    }

    /** @test */
    public function fallback_receives_additional_arguments(): void
    {
        $received_arg = null;

        $router = new HandleRoute('GET','/unknown', 'fallback_arg');
        $router->fallback(function ($arg) use (&$received_arg) {
            $received_arg = $arg;
        })->dispatch();

        $this->assertEquals('fallback_arg', $received_arg);
    }

    /** @test */
    public function no_additional_arguments_works_correctly(): void
    {
        $executed = false;

        $router = new HandleRoute('GET', '/test');
        $router->get('/test', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function dynamic_route_with_single_parameter(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/users/123');
        $router->get('/users/{id}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '123'], $received_params);
    }

    /** @test */
    public function dynamic_route_with_multiple_parameters(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/users/456/posts/789');
        $router->get('/users/{userId}/posts/{postId}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['userId' => '456', 'postId' => '789'], $received_params);
    }

    /** @test */
    public function dynamic_route_with_string_parameter(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/posts/my-blog-post');
        $router->get('/posts/{slug}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['slug' => 'my-blog-post'], $received_params);
    }

    /** @test */
    public function dynamic_route_with_controller_array(): void
    {
        $router = new HandleRoute('GET', '/users/999');
        $router->get('/users/{id}', [DynamicController::class, 'show'])->dispatch();

        $this->assertEquals(['id' => '999'], DynamicController::$received_params);
    }

    /** @test */
    public function dynamic_route_with_invokeable_controller(): void
    {
        $router = new HandleRoute('GET', '/products/abc123');
        $router->get('/products/{sku}', InvokeableDynamicController::class)->dispatch();

        $this->assertEquals(['sku' => 'abc123'], InvokeableDynamicController::$received_params);
    }

    /** @test */
    public function dynamic_route_with_additional_arguments(): void
    {
        $logger = 'test_logger';
        $received_params = null;
        $received_logger = null;

        $router = new HandleRoute('GET', '/items/42', $logger);
        $router->get('/items/{itemId}', function ($params, $log) use (&$received_params, &$received_logger) {
            $received_params = $params;
            $received_logger = $log;
        })->dispatch();

        $this->assertEquals(['itemId' => '42'], $received_params);
        $this->assertEquals('test_logger', $received_logger);
    }

    /** @test */
    public function static_and_dynamic_routes_coexist(): void
    {
        $static_executed = false;
        $dynamic_params = null;

        $router = new HandleRoute('GET', '/users/123');
        $router->get('/users', function () use (&$static_executed) {
            $static_executed = true;
        });
        $router->get('/users/{id}', function ($params) use (&$dynamic_params) {
            $dynamic_params = $params;
        })->dispatch();

        $this->assertFalse($static_executed);
        $this->assertEquals(['id' => '123'], $dynamic_params);
    }

    /** @test */
    public function static_route_takes_priority_over_dynamic(): void
    {
        $static_executed = false;
        $dynamic_executed = false;

        $router = new HandleRoute('GET', '/users/create');
        $router->get('/users/create', function () use (&$static_executed) {
            $static_executed = true;
        });
        $router->get('/users/{id}', function () use (&$dynamic_executed) {
            $dynamic_executed = true;
        })->dispatch();

        $this->assertTrue($static_executed);
        $this->assertFalse($dynamic_executed);
    }

    /** @test */
    public function dynamic_route_matches_alphanumeric_with_hyphens(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/articles/my-article-123-title');
        $router->get('/articles/{slug}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['slug' => 'my-article-123-title'], $received_params);
    }

    /** @test */
    public function dynamic_route_does_not_match_with_trailing_slash(): void
    {
        $executed = false;

        $router = new HandleRoute('GET', '/users/123/extra');
        $router->get('/users/{id}', function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertFalse($executed);
    }

    /** @test */
    public function dynamic_route_works_with_different_http_methods(): void
    {
        $received_params = null;

        $router = new HandleRoute('POST', '/users/456');
        $router->post('/users/{id}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '456'], $received_params);
    }

    /** @test */
    public function dynamic_route_works_with_delete_method(): void
    {
        $received_params = null;

        $router = new HandleRoute('DELETE', '/posts/789');
        $router->delete('/posts/{id}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '789'], $received_params);
    }

    /** @test */
    public function dynamic_route_with_fallback(): void
    {
        $fallback_executed = false;

        $router = new HandleRoute('GET', '/unknown/path');
        $router->get('/users/{id}', function () {
        });
        $router->fallback(function () use (&$fallback_executed) {
            $fallback_executed = true;
        })->dispatch();

        $this->assertTrue($fallback_executed);
    }

    /** @test */
    public function mixed_static_and_dynamic_routes_in_chain(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/api/v1/users/100/profile');
        $router
            ->get('/', 'Home')
            ->get('/about', 'About')
            ->get('/users/{id}', function () {
            })
            ->get('/api/v1/users/{userId}/profile', function ($params) use (&$received_params) {
                $received_params = $params;
            })
            ->get('/contact', 'Contact')
            ->dispatch();

        $this->assertEquals(['userId' => '100'], $received_params);
    }

    /** @test */
    public function compile_routes_returns_array_with_static_and_dynamic_keys(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get('/users', [UserController::class, 'index']);
        $router->get('/posts', 'Posts Page');

        $compiled = $router->compileRoutes();

        $this->assertIsArray($compiled);
        $this->assertArrayHasKey('static', $compiled);
        $this->assertArrayHasKey('dynamic', $compiled);
    }

    /** @test */
    public function compile_routes_includes_static_routes_in_correct_format(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get('/users', [TestController::class, 'show']);
        $router->post('/submit', [TestController::class, 'create']);

        $compiled = $router->compileRoutes();

        $this->assertArrayHasKey('GET:/users', $compiled['static']);
        $this->assertArrayHasKey('POST:/submit', $compiled['static']);
    }

    /** @test */
    public function compile_routes_includes_dynamic_routes_in_correct_format(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get('/users/{id}', [DynamicController::class, 'show']);

        $compiled = $router->compileRoutes();

        $this->assertCount(1, $compiled['dynamic']);
        $this->assertArrayHasKey('method', $compiled['dynamic'][0]);
        $this->assertArrayHasKey('pattern', $compiled['dynamic'][0]);
        $this->assertArrayHasKey('regex', $compiled['dynamic'][0]);
        $this->assertArrayHasKey('params', $compiled['dynamic'][0]);
        $this->assertArrayHasKey('action', $compiled['dynamic'][0]);
        $this->assertEquals('GET', $compiled['dynamic'][0]['method']);
        $this->assertEquals('/users/{id}', $compiled['dynamic'][0]['pattern']);
        $this->assertEquals(['id'], $compiled['dynamic'][0]['params']);
    }

    /** @test */
    public function compile_routes_with_both_static_and_dynamic_routes(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get('/home', 'Home Page');
        $router->get('/users/{id}', [DynamicController::class, 'show']);
        $router->post('/submit', [TestController::class, 'create']);
        $router->delete('/posts/{postId}', [DynamicController::class, 'show']);

        $compiled = $router->compileRoutes();

        $this->assertCount(2, $compiled['static']);
        $this->assertCount(2, $compiled['dynamic']);
    }

    /** @test */
    public function set_cached_routes_returns_instance_for_chaining(): void
    {
        $router = new HandleRoute('GET', '/');
        $cached = ['static' => [], 'dynamic' => []];

        $result = $router->setCachedRoutes($cached);

        $this->assertSame($router, $result);
    }

    /** @test */
    public function set_cached_routes_loads_static_routes(): void
    {
        $action = function () {
            echo 'Cached action';
        };
        $cached = [
            'static' => [
                'GET:/cached' => $action
            ],
            'dynamic' => []
        ];

        $router = new HandleRoute('GET', '/cached');
        $router->setCachedRoutes($cached);

        ob_start();
        $router->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Cached action', $output);
    }

    /** @test */
    public function set_cached_routes_loads_dynamic_routes(): void
    {
        $action = function ($params) use (&$received_params) {
            $received_params = $params;
        };
        $received_params = null;

        $cached = [
            'static' => [],
            'dynamic' => [
                [
                    'method' => 'GET',
                    'pattern' => '/users/{id}',
                    'regex' => '#^/users/([^/]+)$#',
                    'params' => ['id'],
                    'action' => $action
                ]
            ]
        ];

        $router = new HandleRoute('GET', '/users/123');
        $router->setCachedRoutes($cached);
        $router->dispatch();

        $this->assertEquals(['id' => '123'], $received_params);
    }

    /** @test */
    public function cached_routes_work_with_controller_arrays(): void
    {
        $cached = [
            'static' => [
                'GET:/test' => function () {
                    (new CacheTestController())->index();
                }
            ],
            'dynamic' => []
        ];

        CacheTestController::$executed = false;

        $router = new HandleRoute('GET', '/test');
        $router->setCachedRoutes($cached);

        ob_start();
        $router->dispatch();
        $output = ob_get_clean();

        $this->assertTrue(CacheTestController::$executed);
        $this->assertEquals('Cached controller executed', $output);
    }

    /** @test */
    public function compile_and_set_cached_routes_round_trip(): void
    {
        // First router: define routes and compile
        $router1 = new HandleRoute('GET', '/');
        $router1->get('/test', [TestController::class, 'index']);
        $compiled = $router1->compileRoutes();

        // Second router: load compiled routes
        $router2 = new HandleRoute('GET', '/test');
        $router2->setCachedRoutes($compiled);
        $router2->dispatch();

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function compile_and_set_cached_dynamic_routes_round_trip(): void
    {
        // First router: define routes and compile
        $router1 = new HandleRoute('GET', '/');
        $router1->get('/users/{userId}/posts/{postId}', [DynamicControllerMultiParams::class, 'handle']);
        $compiled = $router1->compileRoutes();

        // Second router: load compiled routes
        $router2 = new HandleRoute('GET', '/users/42/posts/99');
        $router2->setCachedRoutes($compiled);
        $router2->dispatch();

        $this->assertEquals(['userId' => '42', 'postId' => '99'], DynamicControllerMultiParams::$received_params);
    }

    /** @test */
    public function set_cached_routes_handles_missing_static_key(): void
    {
        $router = new HandleRoute('GET', '/');
        $cached = ['dynamic' => []];

        $router->setCachedRoutes($cached);
        $result = $router->dispatch();

        $this->assertFalse($result);
    }

    /** @test */
    public function set_cached_routes_handles_missing_dynamic_key(): void
    {
        $action = function () {
            echo 'Test';
        };

        $router = new HandleRoute('GET', '/test');
        $cached = ['static' => ['GET:/test' => $action]];

        $router->setCachedRoutes($cached);

        ob_start();
        $router->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Test', $output);
    }

    /** @test */
    public function set_cached_routes_handles_non_array_static_value(): void
    {
        $router = new HandleRoute('GET', '/');
        $cached = ['static' => 'invalid', 'dynamic' => []];

        $router->setCachedRoutes($cached);
        $result = $router->dispatch();

        $this->assertFalse($result);
    }

    /** @test */
    public function set_cached_routes_handles_non_array_dynamic_value(): void
    {
        $action = function () {
            echo 'Test';
        };

        $router = new HandleRoute('GET', '/test');
        $cached = ['static' => ['GET:/test' => $action], 'dynamic' => 'invalid'];

        $router->setCachedRoutes($cached);

        ob_start();
        $router->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Test', $output);
    }

    /** @test */
    public function cached_routes_work_with_additional_arguments(): void
    {
        $received_arg = null;
        $action = function ($arg) use (&$received_arg) {
            $received_arg = $arg;
        };

        $cached = [
            'static' => ['GET:/test' => $action],
            'dynamic' => []
        ];

        $router = new HandleRoute('GET', '/test', 'injected_value');
        $router->setCachedRoutes($cached);
        $router->dispatch();

        $this->assertEquals('injected_value', $received_arg);
    }

    /** @test */
    public function cached_dynamic_routes_work_with_additional_arguments(): void
    {
        $received_params = null;
        $received_arg = null;
        $action = function ($params, $arg) use (&$received_params, &$received_arg) {
            $received_params = $params;
            $received_arg = $arg;
        };

        $cached = [
            'static' => [],
            'dynamic' => [
                [
                    'method' => 'GET',
                    'pattern' => '/items/{id}',
                    'regex' => '#^/items/([^/]+)$#',
                    'params' => ['id'],
                    'action' => $action
                ]
            ]
        ];

        $router = new HandleRoute('GET', '/items/456', 'dependency');
        $router->setCachedRoutes($cached);
        $router->dispatch();

        $this->assertEquals(['id' => '456'], $received_params);
        $this->assertEquals('dependency', $received_arg);
    }

    /** @test */
    public function set_cached_routes_can_be_chained_with_fallback(): void
    {
        $fallback_executed = false;
        $cached = ['static' => [], 'dynamic' => []];

        $router = new HandleRoute('GET', '/unknown');
        $router
            ->setCachedRoutes($cached)
            ->fallback(function () use (&$fallback_executed) {
                $fallback_executed = true;
            })
            ->dispatch();

        $this->assertTrue($fallback_executed);
    }

    /** @test */
    public function compile_routes_with_empty_router_returns_empty_arrays(): void
    {
        $router = new HandleRoute('GET', '/');

        $compiled = $router->compileRoutes();

        $this->assertEquals(['static' => [], 'dynamic' => [], 'regex' => []], $compiled);
    }

    /** @test */
    public function is_cacheable_returns_true_for_controller_arrays(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get('/users', [TestController::class, 'index']);
        $router->post('/users', [TestController::class, 'store']);

        $this->assertTrue($router->isCacheable());
    }

    /** @test */
    public function is_cacheable_returns_true_for_invokeable_controllers(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get('/users', InvokeableController::class);
        $router->get('/posts', InvokeableWithoutOutput::class);

        $this->assertTrue($router->isCacheable());
    }

    /** @test */
    public function is_cacheable_returns_true_for_string_responses(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get('/status', 'OK');
        $router->get('/version', 'v1.0.0');

        $this->assertTrue($router->isCacheable());
    }

    /** @test */
    public function is_cacheable_returns_true_for_mixed_cacheable_types(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get('/users', [TestController::class, 'index']);
        $router->get('/home', InvokeableController::class);
        $router->get('/status', 'OK');

        $this->assertTrue($router->isCacheable());
    }

    /** @test */
    public function is_cacheable_returns_false_for_static_route_with_closure(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get('/users', function () {
            echo 'Users';
        });

        $this->assertFalse($router->isCacheable());
    }

    /** @test */
    public function is_cacheable_returns_false_for_dynamic_route_with_closure(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get('/users/{id}', function ($params) {
            echo $params['id'];
        });

        $this->assertFalse($router->isCacheable());
    }

    /** @test */
    public function is_cacheable_returns_false_for_mixed_routes_with_one_closure(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get('/users', [TestController::class, 'index']);
        $router->get('/posts', function () {
            echo 'Posts';
        });

        $this->assertFalse($router->isCacheable());
    }

    /** @test */
    public function is_cacheable_returns_true_for_empty_router(): void
    {
        $router = new HandleRoute('GET', '/');

        $this->assertTrue($router->isCacheable());
    }

    /** @test */
    public function compile_routes_succeeds_with_controller_arrays(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get('/users', [TestController::class, 'index']);
        $router->post('/users', [TestController::class, 'store']);

        $compiled = $router->compileRoutes();

        $this->assertIsArray($compiled);
        $this->assertArrayHasKey('static', $compiled);
        $this->assertCount(2, $compiled['static']);
    }

    /** @test */
    public function compile_routes_succeeds_with_invokeable_controllers(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get('/home', InvokeableController::class);

        $compiled = $router->compileRoutes();

        $this->assertIsArray($compiled);
        $this->assertCount(1, $compiled['static']);
    }

    /** @test */
    public function compile_routes_succeeds_with_string_responses(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get('/status', 'OK');

        $compiled = $router->compileRoutes();

        $this->assertIsArray($compiled);
        $this->assertCount(1, $compiled['static']);
    }

    /** @test */
    public function compile_routes_throws_exception_with_static_closure(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot compile routes with closures for caching');

        $router = new HandleRoute('GET', '/');
        $router->get('/users', function () {
            echo 'Users';
        });

        $router->compileRoutes();
    }

    /** @test */
    public function compile_routes_throws_exception_with_dynamic_closure(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Closures cannot be serialized in PHP');

        $router = new HandleRoute('GET', '/');
        $router->get('/users/{id}', function ($params) {
            echo $params['id'];
        });

        $router->compileRoutes();
    }

    /** @test */
    public function compile_routes_throws_exception_with_mixed_routes(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Use controller arrays');

        $router = new HandleRoute('GET', '/');
        $router->get('/users', [TestController::class, 'index']);
        $router->get('/posts', function () {
            echo 'Posts';
        });

        $router->compileRoutes();
    }

    /** @test */
    public function compile_routes_exception_message_includes_solution(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invokeable classes');

        $router = new HandleRoute('GET', '/');
        $router->get('/test', function () {
        });

        $router->compileRoutes();
    }

    /** @test */
    public function is_cacheable_with_dynamic_routes_using_controllers(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get('/users/{id}', [DynamicController::class, 'show']);
        $router->get('/posts/{slug}', InvokeableDynamicController::class);

        $this->assertTrue($router->isCacheable());
    }

    /** @test */
    public function compile_routes_succeeds_with_dynamic_controller_routes(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get('/users/{id}', [DynamicController::class, 'show']);

        $compiled = $router->compileRoutes();

        $this->assertIsArray($compiled);
        $this->assertCount(1, $compiled['dynamic']);
        $this->assertEquals('GET', $compiled['dynamic'][0]['method']);
        $this->assertEquals('/users/{id}', $compiled['dynamic'][0]['pattern']);
    }

    /** @test */
    public function regex_route_single_element_array_matches_and_executes(): void
    {
        $executed = false;

        $router = new HandleRoute('GET', '/about/page');
        $router->get(['#^/about/([^/]+)$#'], function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertTrue($executed);
    }

    /** @test */
    public function regex_route_single_element_array_does_not_pass_params(): void
    {
        $received_arg_count = null;

        $router = new HandleRoute('GET', '/about/page');
        $router->get(['#^/about/([^/]+)$#'], function (...$args) use (&$received_arg_count) {
            $received_arg_count = count($args);
        })->dispatch();

        $this->assertEquals(0, $received_arg_count);
    }

    /** @test */
    public function regex_route_with_single_parameter(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/users/123');
        $router->get(['/users/(\d+)/', ['id']], function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '123'], $received_params);
    }

    /** @test */
    public function regex_route_with_multiple_parameters(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/users/42/posts/99');
        $router->get(['/users/(\d+)/posts/(\d+)/', ['userId', 'postId']], function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['userId' => '42', 'postId' => '99'], $received_params);
    }

    /** @test */
    public function regex_route_with_alphanumeric_slug_pattern(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/articles/my-article-123');
        $router->get(['/articles/([\w-]+)/', ['slug']], function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['slug' => 'my-article-123'], $received_params);
    }

    /** @test */
    public function regex_route_with_controller_array(): void
    {
        $router = new HandleRoute('GET', '/items/999');
        $router->get(['/items/(\d+)/', ['id']], [RegexController::class, 'handle'])->dispatch();

        $this->assertEquals(['id' => '999'], RegexController::$received_params);
    }

    /** @test */
    public function regex_route_with_invokeable_controller(): void
    {
        $router = new HandleRoute('GET', '/products/abc123');
        $router->get(['/products/([\w]+)/', ['sku']], InvokeableRegexController::class)->dispatch();

        $this->assertEquals(['sku' => 'abc123'], InvokeableRegexController::$received_params);
    }

    /** @test */
    public function regex_route_with_additional_arguments(): void
    {
        $logger = 'test_logger';
        $received_params = null;
        $received_logger = null;

        $router = new HandleRoute('GET', '/api/456', $logger);
        $router->get(['/api/(\d+)/', ['id']], function ($params, $log) use (&$received_params, &$received_logger) {
            $received_params = $params;
            $received_logger = $log;
        })->dispatch();

        $this->assertEquals(['id' => '456'], $received_params);
        $this->assertEquals('test_logger', $received_logger);
    }

    /** @test */
    public function regex_route_with_pre_compiled_delimiters(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/users/789');
        $router->get(['#^/users/(\d+)$#', ['id']], function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '789'], $received_params);
    }

    /** @test */
    public function regex_route_does_not_match_wrong_pattern(): void
    {
        $executed = false;

        $router = new HandleRoute('GET', '/users/abc');
        $router->get(['/users/(\d+)/', ['id']], function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertFalse($executed);
    }

    /** @test */
    public function regex_route_does_not_match_wrong_method(): void
    {
        $executed = false;

        $router = new HandleRoute('POST', '/users/123');
        $router->get(['/users/(\d+)/', ['id']], function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertFalse($executed);
    }

    /** @test */
    public function regex_route_works_with_post_method(): void
    {
        $received_params = null;

        $router = new HandleRoute('POST', '/api/create/456');
        $router->post(['/api/create/(\d+)/', ['id']], function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '456'], $received_params);
    }

    /** @test */
    public function regex_route_works_with_put_method(): void
    {
        $received_params = null;

        $router = new HandleRoute('PUT', '/api/update/789');
        $router->put(['/api/update/(\d+)/', ['id']], function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '789'], $received_params);
    }

    /** @test */
    public function regex_route_works_with_delete_method(): void
    {
        $received_params = null;

        $router = new HandleRoute('DELETE', '/api/delete/111');
        $router->delete(['/api/delete/(\d+)/', ['id']], function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '111'], $received_params);
    }

    /** @test */
    public function regex_route_works_with_patch_method(): void
    {
        $received_params = null;

        $router = new HandleRoute('PATCH', '/api/patch/222');
        $router->patch(['/api/patch/(\d+)/', ['id']], function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '222'], $received_params);
    }

    /** @test */
    public function regex_route_works_with_options_method(): void
    {
        $received_params = null;

        $router = new HandleRoute('OPTIONS', '/api/options/333');
        $router->options(['/api/options/(\d+)/', ['id']], function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '333'], $received_params);
    }

    /** @test */
    public function regex_route_works_with_head_method(): void
    {
        $received_params = null;

        $router = new HandleRoute('HEAD', '/api/head/444');
        $router->head(['/api/head/(\d+)/', ['id']], function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '444'], $received_params);
    }

    /** @test */
    public function static_route_takes_priority_over_regex_route(): void
    {
        $static_executed = false;
        $regex_executed = false;

        $router = new HandleRoute('GET', '/users/create');
        $router->get('/users/create', function () use (&$static_executed) {
            $static_executed = true;
        });
        $router->get(['/users/(\w+)/', ['action']], function () use (&$regex_executed) {
            $regex_executed = true;
        })->dispatch();

        $this->assertTrue($static_executed);
        $this->assertFalse($regex_executed);
    }

    /** @test */
    public function dynamic_route_takes_priority_over_regex_route(): void
    {
        $dynamic_executed = false;
        $regex_executed = false;

        $router = new HandleRoute('GET', '/products/123');
        $router->get('/products/{id}', function () use (&$dynamic_executed) {
            $dynamic_executed = true;
        });
        $router->get(['/products/(\d+)/', ['id']], function () use (&$regex_executed) {
            $regex_executed = true;
        })->dispatch();

        $this->assertTrue($dynamic_executed);
        $this->assertFalse($regex_executed);
    }

    /** @test */
    public function regex_route_executes_when_static_and_dynamic_do_not_match(): void
    {
        $regex_executed = false;

        $router = new HandleRoute('GET', '/api/v2/123');
        $router->get('/api/v1', function () {
        });
        $router->get('/api/v1/{id}', function () {
        });
        $router->get(['/api/v2/(\d+)/', ['id']], function () use (&$regex_executed) {
            $regex_executed = true;
        })->dispatch();

        $this->assertTrue($regex_executed);
    }

    /** @test */
    public function regex_route_handles_query_strings(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/items/555?sort=asc&page=2');
        $router->get(['/items/(\d+)/', ['id']], function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '555'], $received_params);
    }

    /** @test */
    public function regex_route_returns_instance_for_chaining(): void
    {
        $router = new HandleRoute('GET', '/test');

        $result = $router->get(['/test/', []]);

        $this->assertSame($router, $result);
    }

    /** @test */
    public function regex_route_invalid_pattern_skips_silently(): void
    {
        $executed = false;

        $router = new HandleRoute('GET', '/test');
        $router->get(['(?P<invalid>', ['id']], function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertFalse($executed);
    }

    /** @test */
    public function regex_route_param_count_mismatch_skips_silently(): void
    {
        $executed = false;

        $router = new HandleRoute('GET', '/users/123/posts/456');
        $router->get(['/users/(\d+)/posts/(\d+)/', ['id']], function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertFalse($executed);
    }

    /** @test */
    public function regex_route_empty_pattern_skips_silently(): void
    {
        $executed = false;

        $router = new HandleRoute('GET', '/test');
        $router->get(['', ['id']], function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertFalse($executed);
    }

    /** @test */
    public function regex_route_invalid_array_format_three_elements_skips(): void
    {
        $executed = false;

        $router = new HandleRoute('GET', '/test');
        $router->get(['/pattern/', ['id'], 'extra'], function () use (&$executed) {
            $executed = true;
        })->dispatch();

        $this->assertFalse($executed);
    }

    /** @test */
    public function regex_route_with_null_action_does_not_execute(): void
    {
        $router = new HandleRoute('GET', '/test/123');

        ob_start();
        $router->get(['/test/(\d+)/', ['id']], null)->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('', $output);
    }

    /** @test */
    public function compile_routes_includes_regex_routes_in_correct_format(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get(['/users/(\d+)/', ['id']], [RegexController::class, 'handle']);

        $compiled = $router->compileRoutes();

        $this->assertCount(1, $compiled['regex']);
        $this->assertArrayHasKey('method', $compiled['regex'][0]);
        $this->assertArrayHasKey('pattern', $compiled['regex'][0]);
        $this->assertArrayHasKey('regex', $compiled['regex'][0]);
        $this->assertArrayHasKey('params', $compiled['regex'][0]);
        $this->assertArrayHasKey('action', $compiled['regex'][0]);
        $this->assertEquals('GET', $compiled['regex'][0]['method']);
        $this->assertEquals('/users/(\d+)/', $compiled['regex'][0]['pattern']);
        $this->assertEquals(['id'], $compiled['regex'][0]['params']);
    }

    /** @test */
    public function is_cacheable_returns_true_for_regex_routes_with_controllers(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get(['/users/(\d+)/', ['id']], [RegexController::class, 'handle']);
        $router->get(['/posts/(\d+)/', ['id']], InvokeableRegexController::class);

        $this->assertTrue($router->isCacheable());
    }

    /** @test */
    public function is_cacheable_returns_false_for_regex_route_with_closure(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get(['/users/(\d+)/', ['id']], function ($params) {
            echo $params['id'];
        });

        $this->assertFalse($router->isCacheable());
    }

    /** @test */
    public function compile_and_set_cached_regex_routes_round_trip(): void
    {
        $router1 = new HandleRoute('GET', '/');
        $router1->get(['/users/(\d+)/posts/(\d+)/', ['userId', 'postId']], [DynamicControllerMultiParams::class, 'handle']);
        $compiled = $router1->compileRoutes();

        $router2 = new HandleRoute('GET', '/users/42/posts/99');
        $router2->setCachedRoutes($compiled);
        $router2->dispatch();

        $this->assertEquals(['userId' => '42', 'postId' => '99'], DynamicControllerMultiParams::$received_params);
    }

    /** @test */
    public function set_cached_routes_loads_regex_routes(): void
    {
        $received_params = null;
        $action = function ($params) use (&$received_params) {
            $received_params = $params;
        };

        $cached = [
            'static' => [],
            'dynamic' => [],
            'regex' => [
                [
                    'method' => 'GET',
                    'pattern' => '/api/(\d+)/',
                    'regex' => '#^/api/(\d+)/$#',
                    'params' => ['id'],
                    'action' => $action
                ]
            ]
        ];

        $router = new HandleRoute('GET', '/api/789/');
        $router->setCachedRoutes($cached);
        $router->dispatch();

        $this->assertEquals(['id' => '789'], $received_params);
    }

    /** @test */
    public function set_cached_routes_handles_missing_regex_key(): void
    {
        $action = function () {
            echo 'Test';
        };

        $router = new HandleRoute('GET', '/test');
        $cached = ['static' => ['GET:/test' => $action], 'dynamic' => []];

        $router->setCachedRoutes($cached);

        ob_start();
        $router->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Test', $output);
    }

    /** @test */
    public function set_cached_routes_handles_non_array_regex_value(): void
    {
        $action = function () {
            echo 'Test';
        };

        $router = new HandleRoute('GET', '/test');
        $cached = ['static' => ['GET:/test' => $action], 'dynamic' => [], 'regex' => 'invalid'];

        $router->setCachedRoutes($cached);

        ob_start();
        $router->dispatch();
        $output = ob_get_clean();

        $this->assertEquals('Test', $output);
    }

    /** @test */
    public function compile_routes_with_all_three_route_types(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get('/static', 'Static');
        $router->get('/dynamic/{id}', [DynamicController::class, 'show']);
        $router->get(['/regex/(\d+)/', ['id']], [RegexController::class, 'handle']);

        $compiled = $router->compileRoutes();

        $this->assertCount(1, $compiled['static']);
        $this->assertCount(1, $compiled['dynamic']);
        $this->assertCount(1, $compiled['regex']);
    }

    /** @test */
    public function regex_route_fallback_executes_when_pattern_does_not_match(): void
    {
        $fallback_executed = false;

        $router = new HandleRoute('GET', '/nomatch');
        $router->get(['/users/(\d+)/', ['id']], function () {
        });
        $router->fallback(function () use (&$fallback_executed) {
            $fallback_executed = true;
        })->dispatch();

        $this->assertTrue($fallback_executed);
    }

    /** @test */
    public function regex_route_single_element_with_controller_array(): void
    {
        $router = new HandleRoute('GET', '/about/section');
        $router->get(['#^/about/(\w+)$#'], [RegexController::class, 'handle'])->dispatch();

        $this->assertNull(RegexController::$received_params);
    }

    /** @test */
    public function mixed_static_dynamic_and_regex_routes_dispatch_correctly(): void
    {
        $static_executed = false;
        $dynamic_params = null;
        $regex_params = null;

        $router = new HandleRoute('GET', '/regex/999');
        $router->get('/static', function () use (&$static_executed) {
            $static_executed = true;
        });
        $router->get('/dynamic/{id}', function ($params) use (&$dynamic_params) {
            $dynamic_params = $params;
        });
        $router->get(['/regex/(\d+)/', ['id']], function ($params) use (&$regex_params) {
            $regex_params = $params;
        })->dispatch();

        $this->assertFalse($static_executed);
        $this->assertNull($dynamic_params);
        $this->assertEquals(['id' => '999'], $regex_params);
    }

    /** @test */
    public function regex_route_with_multiple_routes_only_first_match_executes(): void
    {
        $first_executed = false;
        $second_executed = false;

        $router = new HandleRoute('GET', '/items/123');
        $router->get(['/items/(\d+)/', ['id']], function () use (&$first_executed) {
            $first_executed = true;
        });
        $router->get(['/items/(\w+)/', ['slug']], function () use (&$second_executed) {
            $second_executed = true;
        })->dispatch();

        $this->assertTrue($first_executed);
        $this->assertFalse($second_executed);
    }

    /** @test */
    public function regex_route_single_element_with_additional_args(): void
    {
        $received_arg = null;

        $router = new HandleRoute('GET', '/test/page', 'logger');
        $router->get(['#^/test/(\w+)$#'], function ($arg) use (&$received_arg) {
            $received_arg = $arg;
        })->dispatch();

        $this->assertEquals('logger', $received_arg);
    }

    /** @test */
    public function compile_routes_throws_exception_with_regex_closure(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot compile routes with closures for caching');

        $router = new HandleRoute('GET', '/');
        $router->get(['/users/(\d+)/', ['id']], function ($params) {
            echo $params['id'];
        });

        $router->compileRoutes();
    }

    /** @test */
    public function regex_route_chains_with_other_route_types(): void
    {
        $regex_params = null;

        $router = new HandleRoute('GET', '/api/777');
        $router->get('/static', 'Static');
        $router->get('/dynamic/{id}', function () {
        });
        $router->get(['/api/(\d+)/', ['id']], function ($params) use (&$regex_params) {
            $regex_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '777'], $regex_params);
    }

    /** @test */
    public function regex_route_with_complex_multi_segment_pattern(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/v1/users/42/posts/99/comments/7');
        $router->get(['/v1/users/(\d+)/posts/(\d+)/comments/(\d+)/', ['userId', 'postId', 'commentId']], function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['userId' => '42', 'postId' => '99', 'commentId' => '7'], $received_params);
    }

    /** @test */
    public function optional_parameter_at_end_matches_without_param(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/users');
        $router->get('/users/{id?}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals([], $received_params);
    }

    /** @test */
    public function optional_parameter_at_end_matches_with_param(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/users/123');
        $router->get('/users/{id?}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '123'], $received_params);
    }

    /** @test */
    public function optional_parameter_at_start_matches_without_param(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/posts');
        $router->get('/{id?}/posts', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals([], $received_params);
    }

    /** @test */
    public function optional_parameter_at_start_matches_with_param(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/123/posts');
        $router->get('/{id?}/posts', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '123'], $received_params);
    }

    /** @test */
    public function optional_parameter_in_middle_matches_without_param(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/api/users/posts');
        $router->get('/api/{version?}/users/posts', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals([], $received_params);
    }

    /** @test */
    public function optional_parameter_in_middle_matches_with_param(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/api/v1/users/posts');
        $router->get('/api/{version?}/users/posts', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['version' => 'v1'], $received_params);
    }

    /** @test */
    public function multiple_optional_parameters_matches_without_any(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/users');
        $router->get('/users/{id?}/{name?}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals([], $received_params);
    }

    /** @test */
    public function multiple_optional_parameters_matches_with_first_only(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/users/123');
        $router->get('/users/{id?}/{name?}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '123'], $received_params);
    }

    /** @test */
    public function multiple_optional_parameters_matches_with_both(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/users/123/john');
        $router->get('/users/{id?}/{name?}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '123', 'name' => 'john'], $received_params);
    }

    /** @test */
    public function mixed_required_and_optional_parameters(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/users/123/posts');
        $router->get('/users/{id}/posts/{slug?}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '123'], $received_params);
    }

    /** @test */
    public function mixed_required_and_optional_parameters_with_optional_present(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/users/123/posts/my-post');
        $router->get('/users/{id}/posts/{slug?}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '123', 'slug' => 'my-post'], $received_params);
    }

    /** @test */
    public function optional_parameter_with_controller_array(): void
    {
        $router = new HandleRoute('GET', '/items');
        $router->get('/items/{id?}', [DynamicController::class, 'show'])->dispatch();

        $this->assertEquals([], DynamicController::$received_params);
    }

    /** @test */
    public function optional_parameter_with_controller_array_param_present(): void
    {
        $router = new HandleRoute('GET', '/items/999');
        $router->get('/items/{id?}', [DynamicController::class, 'show'])->dispatch();

        $this->assertEquals(['id' => '999'], DynamicController::$received_params);
    }

    /** @test */
    public function optional_parameter_with_invokeable_controller(): void
    {
        $router = new HandleRoute('GET', '/products/abc');
        $router->get('/products/{sku?}', InvokeableDynamicController::class)->dispatch();

        $this->assertEquals(['sku' => 'abc'], InvokeableDynamicController::$received_params);
    }

    /** @test */
    public function optional_parameter_works_with_post_method(): void
    {
        $received_params = null;

        $router = new HandleRoute('POST', '/api/create');
        $router->post('/api/create/{id?}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals([], $received_params);
    }

    /** @test */
    public function optional_parameter_works_with_put_method(): void
    {
        $received_params = null;

        $router = new HandleRoute('PUT', '/api/update/789');
        $router->put('/api/update/{id?}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['id' => '789'], $received_params);
    }

    /** @test */
    public function optional_parameter_works_with_delete_method(): void
    {
        $received_params = null;

        $router = new HandleRoute('DELETE', '/api/delete');
        $router->delete('/api/delete/{id?}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals([], $received_params);
    }

    /** @test */
    public function optional_parameter_with_additional_arguments(): void
    {
        $logger = 'test_logger';
        $received_params = null;
        $received_logger = null;

        $router = new HandleRoute('GET', '/items', $logger);
        $router->get('/items/{id?}', function ($params, $log) use (&$received_params, &$received_logger) {
            $received_params = $params;
            $received_logger = $log;
        })->dispatch();

        $this->assertEquals([], $received_params);
        $this->assertEquals('test_logger', $received_logger);
    }

    /** @test */
    public function optional_parameter_handles_query_strings(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/search/query?page=1');
        $router->get('/search/{term?}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['term' => 'query'], $received_params);
    }

    /** @test */
    public function compile_routes_includes_optional_params(): void
    {
        $router = new HandleRoute('GET', '/');
        $router->get('/users/{id}/posts/{slug?}', [DynamicController::class, 'show']);

        $compiled = $router->compileRoutes();

        $this->assertCount(1, $compiled['dynamic']);
        $this->assertArrayHasKey('optional_params', $compiled['dynamic'][0]);
        $this->assertEquals(['slug'], $compiled['dynamic'][0]['optional_params']);
        $this->assertEquals(['id', 'slug'], $compiled['dynamic'][0]['params']);
    }

    /** @test */
    public function compile_and_set_cached_optional_params_round_trip(): void
    {
        $router1 = new HandleRoute('GET', '/');
        $router1->get('/users/{userId}/posts/{postId?}', [DynamicControllerMultiParams::class, 'handle']);
        $compiled = $router1->compileRoutes();

        $router2 = new HandleRoute('GET', '/users/42/posts');
        $router2->setCachedRoutes($compiled);
        $router2->dispatch();

        $this->assertEquals(['userId' => '42'], DynamicControllerMultiParams::$received_params);
    }

    /** @test */
    public function compile_and_set_cached_optional_params_with_value_round_trip(): void
    {
        $router1 = new HandleRoute('GET', '/');
        $router1->get('/users/{userId}/posts/{postId?}', [DynamicControllerMultiParams::class, 'handle']);
        $compiled = $router1->compileRoutes();

        $router2 = new HandleRoute('GET', '/users/42/posts/99');
        $router2->setCachedRoutes($compiled);
        $router2->dispatch();

        $this->assertEquals(['userId' => '42', 'postId' => '99'], DynamicControllerMultiParams::$received_params);
    }

    /** @test */
    public function optional_parameter_returns_instance_for_chaining(): void
    {
        $router = new HandleRoute('GET', '/test');

        $result = $router->get('/test/{id?}');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function backward_compatibility_required_params_still_work(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/users/456/posts/789');
        $router->get('/users/{userId}/posts/{postId}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['userId' => '456', 'postId' => '789'], $received_params);
    }

    /** @test */
    public function optional_and_required_params_complex_pattern(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/api/v1/users/123/posts');
        $router->get('/api/{version?}/users/{id}/posts/{slug?}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['version' => 'v1', 'id' => '123'], $received_params);
    }

    /** @test */
    public function all_optional_parameters_none_provided(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/search');
        $router->get('/search/{query?}/{category?}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals([], $received_params);
    }

    /** @test */
    public function optional_parameter_with_hyphenated_value(): void
    {
        $received_params = null;

        $router = new HandleRoute('GET', '/articles/my-article-slug');
        $router->get('/articles/{slug?}', function ($params) use (&$received_params) {
            $received_params = $params;
        })->dispatch();

        $this->assertEquals(['slug' => 'my-article-slug'], $received_params);
    }
}

class TestControllerWithArgs
{
    public static $received_args = [];

    public function handleWithArgs(...$args): void
    {
        self::$received_args = $args;
    }
}

class InvokeableControllerWithArgs
{
    public static $received_arg = null;

    public function __invoke($arg = null): void
    {
        self::$received_arg = $arg;
    }
}

class DynamicController
{
    public static $received_params = null;

    public function show($params): void
    {
        self::$received_params = $params;
    }
}

class InvokeableDynamicController
{
    public static $received_params = null;

    public function __invoke($params): void
    {
        self::$received_params = $params;
    }
}

class CacheTestController
{
    public static $executed = false;

    public function index(): void
    {
        self::$executed = true;
        echo 'Cached controller executed';
    }
}

class RegexController
{
    public static $received_params = null;

    public function handle($params = null): void
    {
        self::$received_params = $params;
    }
}

class InvokeableRegexController
{
    public static $received_params = null;

    public function __invoke($params = null): void
    {
        self::$received_params = $params;
    }
}
