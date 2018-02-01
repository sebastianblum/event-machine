<?php

declare(strict_types=1);

namespace Prooph\EventMachineTest\JsonSchema\Type;

use Prooph\EventMachine\JsonSchema\JsonSchema;
use Prooph\EventMachine\JsonSchema\Type\ObjectType;
use Prooph\EventMachineTest\BasicTestCase;

final class ObjectTypeTest extends BasicTestCase
{
    /**
     * @test
     */
    public function it_converts_to_array()
    {
        $object = new ObjectType([
            'name' => JsonSchema::string(),
        ]);

        $this->assertEquals([
            'type' => JsonSchema::TYPE_OBJECT,
            'properties' => [
                'name' => [
                    'type' => JsonSchema::TYPE_STRING,
                ]
            ],
            'required' => ["name"],
            'additionalProperties' => false,
        ], $object->toArray());
    }

    /**
     * @test
     */
    public function it_can_be_a_nullable_type()
    {
        $object = (new ObjectType([
            'name' => JsonSchema::string(),
        ]))->asNullable();

        $this->assertEquals([
            'type' => [JsonSchema::TYPE_OBJECT, JsonSchema::TYPE_NULL],
            'properties' => [
                'name' => [
                    'type' => JsonSchema::TYPE_STRING,
                ]
            ],
            'required' => ["name"],
            'additionalProperties' => false,
        ], $object->toArray());
    }

    /**
     * @test
     */
    public function it_can_have_optional_properties()
    {
        $object = (new ObjectType(
            [
                'name' => JsonSchema::string(),
            ],
            [
                'age' => JsonSchema::integer()->withRange(18, 150),
            ]))->asNullable();

        $this->assertEquals([
            'type' => [JsonSchema::TYPE_OBJECT, JsonSchema::TYPE_NULL],
            'properties' => [
                'name' => [
                    'type' => JsonSchema::TYPE_STRING,
                ],
                'age' => [
                    'type' => JsonSchema::TYPE_INT,
                    'minimum' => 18,
                    'maximum' => 150
                ]
            ],
            'required' => ["name"],
            'additionalProperties' => false,
        ], $object->toArray());
    }

    /**
     * @test
     */
    public function it_can_only_have_optional_props()
    {
        $object = (new ObjectType())->withMergedOptionalProps(['age' => JsonSchema::integer()->withRange(18, 150)]);

        $this->assertEquals([
            'type' => JsonSchema::TYPE_OBJECT,
            'properties' => [
                'age' => [
                    'type' => JsonSchema::TYPE_INT,
                    'minimum' => 18,
                    'maximum' => 150
                ]
            ],
            'required' => [],
            'additionalProperties' => false,
        ], $object->toArray());
    }

    /**
     * @test
     */
    public function it_can_add_more_required_props()
    {
        $object = new ObjectType([
            'name' => JsonSchema::string(),
        ]);

        $object = $object->withMergedRequiredProps(['id' => JsonSchema::string()]);

        $this->assertEquals([
            'type' => JsonSchema::TYPE_OBJECT,
            'properties' => [
                'name' => [
                    'type' => JsonSchema::TYPE_STRING,
                ],
                'id' => [
                    'type' => JsonSchema::TYPE_STRING,
                ]
            ],
            'required' => ["name", "id"],
            'additionalProperties' => false,
        ], $object->toArray());
    }
}
