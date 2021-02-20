<?php

declare(strict_types=1);

namespace Zp\Supple\Elasticsearch;

use JsonSerializable;

final class IndexMappings implements JsonSerializable
{
    /** @var array<string, mixed> */
    private $mappings;

    /**
     * @param array<string, mixed> $mappings
     */
    public function __construct(array $mappings = [])
    {
        $this->mappings = $mappings;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function add(string $name, $value): void
    {
        $this->mappings[$name] = $value;
    }

    /**
     * @param string $name
     * @param array<mixed> $definition
     */
    public function addProperty(string $name, array $definition): void
    {
        $this->mappings['properties'][$name] = $definition;
    }

    /**
     * @param IndexMappings $other
     * @return bool
     */
    public function compareTo(self $other): bool
    {
        return $this->mappings == $other->mappings;
    }

    public function jsonSerialize(): object
    {
        return (object)(self::sort($this->mappings));
    }

    /**
     * @param array<mixed> $mappings
     * @return array<mixed>
     */
    private static function sort(array $mappings): array
    {
        ksort($mappings, SORT_NATURAL);
        foreach ($mappings as $name => $value) {
            if (is_array($value)) {
                $mappings[$name] = self::sort($value);
            }
        }
        return $mappings;
    }

    /**
     * @return string
     * @throws \JsonException
     */
    public function __toString(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
