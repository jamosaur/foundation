<?php

declare(strict_types=1);

namespace Jamosaur\Foundation\Contracts;

interface TransformerContract
{
    public function transform(mixed $model): array;
}
