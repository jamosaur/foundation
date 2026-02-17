<?php

declare(strict_types=1);

namespace Jamosaur\Foundation\Tests\Fixtures;

use Illuminate\Http\JsonResponse;
use Jamosaur\Foundation\ApiController;

class UserController extends ApiController
{
    public function index(): JsonResponse
    {
        $users = [
            new User(1, 'John Doe', 'john@example.com'),
            new User(2, 'Jane Smith', 'jane@example.com'),
        ];

        return $this->setTransformerNamespace('\\App\\Transformers\\')
            ->transformCollection('users', $users)
            ->respond();

    }

    public function indexWithNamespace(): JsonResponse
    {
        $users = [
            new User(1, 'John Doe', 'john@example.com'),
            new User(2, 'Jane Smith', 'jane@example.com'),
        ];

        return $this->setTransformerNamespace('\\Jamosaur\\Foundation\\Tests\\Fixtures\\')
            ->transformCollection('users', $users)
            ->respond();
    }
}
