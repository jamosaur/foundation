<?php

declare(strict_types=1);

namespace Jamosaur\Foundation\Tests\Unit;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Jamosaur\Foundation\ApiController;
use Jamosaur\Foundation\Serializers\DefaultSerializer;
use Jamosaur\Foundation\Tests\Fixtures\User;
use Jamosaur\Foundation\Tests\Fixtures\UserTransformer;
use Jamosaur\Foundation\Tests\TestCase;
use League\Fractal\Serializer\ArraySerializer;
use Mockery;

class ApiControllerTest extends TestCase
{
    private ApiController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ApiController;
    }

    public function test_respond_returns_json_response(): void
    {
        $response = $this->controller->respond();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @throws \JsonException
     */
    public function test_respond_includes_data_key(): void
    {
        $response = $this->controller->respond();
        $content = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('data', $content);
    }

    /**
     * @throws \JsonException
     */
    public function test_append_body_adds_data_to_response(): void
    {
        $response = $this->controller
            ->appendBody('key', 'value')
            ->respond();

        $content = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals('value', $content['data']['key']);
    }

    public function test_append_body_is_chainable(): void
    {
        $result = $this->controller->appendBody('key', 'value');

        $this->assertInstanceOf(ApiController::class, $result);
    }

    public function test_set_status_code_changes_response_code(): void
    {
        $response = $this->controller
            ->setStatusCode(201)
            ->respond();

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_get_status_code_returns_default_200(): void
    {
        $this->assertEquals(200, $this->controller->getStatusCode());
    }

    public function test_get_status_code_returns_custom_code(): void
    {
        $this->controller->setStatusCode(404);

        $this->assertEquals(404, $this->controller->getStatusCode());
    }

    /**
     * @throws \JsonException
     */
    public function test_append_error_sets_error_message_and_status_code(): void
    {
        $response = $this->controller
            ->appendError('Something went wrong', 500)
            ->respond();

        $content = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals('Something went wrong', $content['data']['error']);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function test_append_error_defaults_to_500_status_code(): void
    {
        $response = $this->controller
            ->appendError('Error')
            ->respond();

        $this->assertEquals(500, $response->getStatusCode());
    }

    /**
     * @throws \JsonException
     */
    public function test_with_pagination_adds_pagination_data_for_paginator(): void
    {
        $items = collect([
            new User(1, 'John', 'john@example.com'),
            new User(2, 'Jane', 'jane@example.com'),
        ]);

        $paginator = new LengthAwarePaginator($items, 50, 2, 1);

        $response = $this->controller
            ->withPagination($paginator)
            ->respond();

        $content = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('pagination', $content);
        $this->assertEquals(50, $content['pagination']['total']);
        $this->assertEquals(2, $content['pagination']['per_page']);
        $this->assertEquals(1, $content['pagination']['current_page']);
        $this->assertEquals(25, $content['pagination']['last_page']);
    }

    /**
     * @throws \JsonException
     */
    public function test_with_pagination_handles_array_data(): void
    {
        $items = [1, 2, 3, 4, 5];

        $response = $this->controller
            ->withPagination($items)
            ->respond();

        $content = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('pagination', $content);
        $this->assertEquals(5, $content['pagination']['total']);
        $this->assertEquals(0, $content['pagination']['per_page']);
        $this->assertEquals(1, $content['pagination']['current_page']);
        $this->assertEquals(1, $content['pagination']['last_page']);
    }

    /**
     * @throws \JsonException
     */
    public function test_with_cursor_pagination_adds_cursor_data(): void
    {
        $cursorPaginator = Mockery::mock(CursorPaginator::class);
        $cursorPaginator->shouldReceive('hasMorePages')->andReturn(true);
        $cursorPaginator->shouldReceive('nextCursor')->andReturn(
            Mockery::mock(['encode' => 'next_cursor_token'])
        );
        $cursorPaginator->shouldReceive('previousCursor')->andReturn(
            Mockery::mock(['encode' => 'prev_cursor_token'])
        );

        $response = $this->controller
            ->withCursorPagination($cursorPaginator)
            ->respond();

        $content = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('pagination', $content);
        $this->assertTrue($content['pagination']['has_more']);
        $this->assertEquals('next_cursor_token', $content['pagination']['next_cursor']);
        $this->assertEquals('prev_cursor_token', $content['pagination']['previous_cursor']);
    }

    public function test_set_serializer_changes_serializer(): void
    {
        $serializer = new ArraySerializer;
        $this->controller->setSerializer($serializer);

        $this->assertSame($serializer, $this->controller->getSerializer());
    }

    public function test_get_serializer_returns_default_serializer(): void
    {
        $serializer = $this->controller->getSerializer();

        $this->assertInstanceOf(DefaultSerializer::class, $serializer);
    }

    public function test_set_transformer_changes_transformer(): void
    {
        $transformer = new UserTransformer;
        $this->controller->setTransformer($transformer);

        $this->assertSame($transformer, $this->controller->getTransformer());
    }

    /**
     * @throws \JsonException
     */
    public function test_transform_item_transforms_single_item(): void
    {
        $user = new User(1, 'John Doe', 'john@example.com');
        $transformer = new UserTransformer;

        $this->controller->setTransformer($transformer);
        $response = $this->controller
            ->transformItem('user', $user)
            ->respond();

        $content = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('user', $content['data']);
        $this->assertEquals(1, $content['data']['user']['id']);
        $this->assertEquals('John Doe', $content['data']['user']['name']);
        $this->assertEquals('john@example.com', $content['data']['user']['email']);
    }

    /**
     * @throws \JsonException
     */
    public function test_transform_collection_transforms_multiple_items(): void
    {
        $users = [
            new User(1, 'John Doe', 'john@example.com'),
            new User(2, 'Jane Smith', 'jane@example.com'),
        ];
        $transformer = new UserTransformer;

        $this->controller->setTransformer($transformer);
        $response = $this->controller
            ->transformCollection('users', $users)
            ->respond();

        $content = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('users', $content['data']);
        $this->assertCount(2, $content['data']['users']);
        $this->assertEquals('John Doe', $content['data']['users'][0]['name']);
        $this->assertEquals('Jane Smith', $content['data']['users'][1]['name']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
