<?php

declare(strict_types=1);

namespace Jamosaur\Foundation\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Jamosaur\Foundation\Middleware\ApiRequestMiddleware;
use Jamosaur\Foundation\Tests\TestCase;

class ApiRequestMiddlewareTest extends TestCase
{
    private ApiRequestMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ApiRequestMiddleware;
    }

    public function test_it_parses_controller_and_action_from_uses(): void
    {
        $request = Request::create('/test', 'GET');
        $route = new Route('GET', '/test', [
            'uses' => 'App\Http\Controllers\UserController@index',
        ]);
        $request->setRouteResolver(fn () => $route);

        $this->middleware->handle($request, function ($req) {
            $this->assertEquals('user', $req->attributes->get('_controller'));
            $this->assertEquals('index', $req->attributes->get('_action'));

            return response('OK');
        });
    }

    public function test_it_parses_controller_and_action_from_controller_key(): void
    {
        $request = Request::create('/test', 'GET');
        $route = new Route('GET', '/test', [
            'controller' => 'App\Http\Controllers\ProductController@show',
        ]);
        $request->setRouteResolver(fn () => $route);

        $this->middleware->handle($request, function ($req) {
            $this->assertEquals('product', $req->attributes->get('_controller'));
            $this->assertEquals('show', $req->attributes->get('_action'));

            return response('OK');
        });
    }

    public function test_it_handles_namespaced_controllers(): void
    {
        $request = Request::create('/test', 'GET');
        $route = new Route('GET', '/test', [
            'uses' => 'App\Http\Controllers\Api\V1\OrderController@create',
        ]);
        $request->setRouteResolver(fn () => $route);

        $this->middleware->handle($request, function ($req) {
            $this->assertEquals('order', $req->attributes->get('_controller'));
            $this->assertEquals('create', $req->attributes->get('_action'));

            return response('OK');
        });
    }

    public function test_it_handles_missing_controller_action(): void
    {
        $request = Request::create('/test', 'GET');
        $route = new Route('GET', '/test', []);
        $request->setRouteResolver(fn () => $route);

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_it_handles_closure_routes(): void
    {
        $request = Request::create('/test', 'GET');
        $route = new Route('GET', '/test', [
            'uses' => function () {
                return 'test';
            },
        ]);
        $request->setRouteResolver(fn () => $route);

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }
}
