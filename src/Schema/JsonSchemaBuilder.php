<?php

namespace Llama\Schema;

/**
 * Helper to build JSON Schemas for structured output.
 */
class JsonSchemaBuilder
{
    /**
     * Create a schema for a simple object with typed properties.
     *
     * @param array<string, string|array> $properties Key-value pairs where value is type ('string', 'integer', 'boolean') or nested schema definition.
     * @param array<string> $required List of required property names. If null, all properties are required.
     * @return array Schema structure
     */
    public static function object(array $properties, ?array $required = null): array
    {
        $schemaProps = [];
        $keys = array_keys($properties);
        
        foreach ($properties as $key => $type) {
            if (is_array($type) && isset($type['type'])) {
                // Nested object or complex type (already a schema array)
                $schemaProps[$key] = $type;
            } elseif (is_array($type)) {
                // Plain array, assume it's a nested structure definition, treat as object
                 $schemaProps[$key] = self::object($type);
            } else {
                $schemaProps[$key] = ['type' => $type];
            }
        }

        return [
            'type' => 'object',
            'properties' => $schemaProps,
            'required' => $required ?? $keys,
            'additionalProperties' => false,
        ];
    }

    /**
     * Create a schema for a list/array of items.
     *
     * @param string|array $itemType Type of items ('string', 'integer') or schema array for objects.
     * @return array Schema structure
     */
    public static function list(string|array $itemType): array
    {
        $items = is_array($itemType) ? $itemType : ['type' => $itemType];
        
        return [
            'type' => 'array',
            'items' => $items,
        ];
    }
    
    /**
     * Helper to encode the schema to string.
     */
    public static function build(array $schema): string
    {
        return json_encode($schema, JSON_THROW_ON_ERROR);
    }
}
