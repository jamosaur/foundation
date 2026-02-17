<?php

declare(strict_types=1);

namespace Jamosaur\Foundation\Tests\Fixtures;

use Illuminate\Http\JsonResponse;
use Jamosaur\Foundation\ApiController;

class TestController extends ApiController
{
    public function index(): JsonResponse
    {
        $users = [
            new User(1, 'John Doe', 'john@example.com'),
            new User(2, 'Jane Smith', 'jane@example.com'),
        ];

        return $this->setTransformer(new UserTransformer)
            ->transformCollection('users', $users)
            ->respond();
    }

    public function show(): JsonResponse
    {
        $user = new User(1, 'John Doe', 'john@example.com');

        return $this->setTransformer(new UserTransformer)
            ->transformItem('user', $user)
            ->respond();
    }
}
