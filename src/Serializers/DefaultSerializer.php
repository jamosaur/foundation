<?php

declare(strict_types=1);

namespace Jamosaur\Foundation\Serializers;

use League\Fractal\Serializer\ArraySerializer;

class DefaultSerializer extends ArraySerializer
{
    public function collection($resourceKey, array $data): array
    {
        return $data;
    }
}
