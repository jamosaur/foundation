<?php

declare(strict_types=1);

namespace Jamosaur\Foundation\Tests\Unit;

use Jamosaur\Foundation\Serializers\DefaultSerializer;
use Jamosaur\Foundation\Tests\TestCase;

class DefaultSerializerTest extends TestCase
{
    private DefaultSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = new DefaultSerializer;
    }

    public function test_it_extends_array_serializer(): void
    {
        $this->assertInstanceOf(\League\Fractal\Serializer\ArraySerializer::class, $this->serializer);
    }

    public function test_collection_returns_data_without_resource_key(): void
    {
        $data = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];

        $result = $this->serializer->collection('users', $data);

        $this->assertEquals($data, $result);
        $this->assertArrayNotHasKey('users', $result);
    }

    public function test_collection_handles_empty_array(): void
    {
        $result = $this->serializer->collection('users', []);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
