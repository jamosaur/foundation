<?php

declare(strict_types=1);

namespace Jamosaur\Foundation\Tests\Unit;

use Jamosaur\Foundation\Contracts\TransformerContract;
use Jamosaur\Foundation\Tests\Fixtures\User;
use Jamosaur\Foundation\Tests\Fixtures\UserTransformer;
use Jamosaur\Foundation\Tests\TestCase;
use League\Fractal\TransformerAbstract;

class TransformerContractTest extends TestCase
{
    public function test_user_transformer_implements_contract(): void
    {
        $transformer = new UserTransformer;

        $this->assertInstanceOf(TransformerContract::class, $transformer);
        $this->assertInstanceOf(TransformerAbstract::class, $transformer);
    }

    public function test_transformer_transforms_model_correctly(): void
    {
        $transformer = new UserTransformer;
        $user = new User(1, 'John Doe', 'john@example.com');

        $result = $transformer->transform($user);

        $this->assertIsArray($result);
        $this->assertEquals([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ], $result);
    }
}
