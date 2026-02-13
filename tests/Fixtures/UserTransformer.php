<?php

declare(strict_types=1);

namespace Jamosaur\Foundation\Tests\Fixtures;

use Jamosaur\Foundation\Contracts\TransformerContract;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract implements TransformerContract
{
    public function transform(mixed $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'email' => $model->email,
        ];
    }
}
