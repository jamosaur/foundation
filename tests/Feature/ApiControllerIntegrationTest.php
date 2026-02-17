<?php

declare(strict_types=1);

namespace Jamosaur\Foundation\Tests\Feature;

use Illuminate\Support\Facades\Route as RouteFacade;
use Jamosaur\Foundation\Exceptions\TransformerMissingException;
use Jamosaur\Foundation\Middleware\ApiRequestMiddleware;
use Jamosaur\Foundation\Tests\Fixtures\TestController;
use Jamosaur\Foundation\Tests\Fixtures\UserController;
use Jamosaur\Foundation\Tests\TestCase;

class ApiControllerIntegrationTest extends TestCase
{
    public function test_full_api_request_with_collection_response(): void
    {
        RouteFacade::get('/api/test', [TestController::class, 'index'])
            ->middleware(ApiRequestMiddleware::class);

        $response = $this->get('/api/test');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'users' => [
                    '*' => ['id', 'name', 'email'],
                ],
            ],
        ]);

        $content = $response->json();
        $this->assertCount(2, $content['data']['users']);
    }

    public function test_full_api_request_with_item_response(): void
    {
        RouteFacade::get('/api/test/{id}', [TestController::class, 'show'])
            ->middleware(ApiRequestMiddleware::class);

        $response = $this->get('/api/test/1');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email'],
            ],
        ]);

        $content = $response->json();
        $this->assertEquals(1, $content['data']['user']['id']);
        $this->assertEquals('John Doe', $content['data']['user']['name']);
    }

    public function test_middleware_integration_with_controller(): void
    {
        RouteFacade::middleware(ApiRequestMiddleware::class)
            ->get('/api/users', [TestController::class, 'index']);

        $this->get('/api/users')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'users' => [
                        [
                            'id' => 1,
                            'name' => 'John Doe',
                            'email' => 'john@example.com',
                        ],
                        [
                            'id' => 2,
                            'name' => 'Jane Smith',
                            'email' => 'jane@example.com',
                        ],
                    ],
                ],
            ]);
    }

    public function test_it_throws_runtime_error_missing_transformer(): void
    {
        RouteFacade::middleware(ApiRequestMiddleware::class)
            ->get('/api/users', [UserController::class, 'index']);

        $this->get('/api/users')
            ->assertStatus(500)
            ->withException(new TransformerMissingException('Transformer class \App\Transformers\UserTransformer does not exist'));
    }

    public function test_it_infers_transformer_from_controller_name(): void
    {
        RouteFacade::middleware(ApiRequestMiddleware::class)
            ->get('/api/users', [UserController::class, 'indexWithNamespace']);

        $this->get('/api/users')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'users' => [
                        [
                            'id' => 1,
                            'name' => 'John Doe',
                            'email' => 'john@example.com',
                        ],
                        [
                            'id' => 2,
                            'name' => 'Jane Smith',
                            'email' => 'jane@example.com',
                        ],
                    ],
                ],
            ]);
    }
}
