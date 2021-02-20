<?php

declare(strict_types=1);

namespace Zp\Supple\Elasticsearch;

use JsonSerializable;

final class IndexTemplate implements JsonSerializable
{
    /** @var string */
    private $name;

    /** @var ?string */
    private $type;

    /** @var array<string> */
    private $indexPatterns;

    /** @var IndexMappings */
    private $mappings;

    /** @var IndexSettings */
    private $settings;

    /**
     * @param string $name
     * @param array<string> $patterns
     * @param IndexMappings $mappings
     * @param IndexSettings $settings
     */
    public function __construct(string $name, array $patterns, IndexMappings $mappings, IndexSettings $settings)
    {
        $this->name = str_replace('\\', '-', strtolower($name));
        $this->indexPatterns = $patterns;
        $this->mappings = $mappings;
        $this->settings = $settings;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @param IndexTemplate $other
     * @return $this
     */
    public function mergeMissingSettingsFrom(self $other): self
    {
        $clone = clone $this;
        $clone->settings = $this->settings->mergeMissingSettingsFrom($other->settings);
        return $clone;
    }

    /**
     * @param IndexTemplate $other
     * @return bool
     */
    public function compareTo(self $other): bool
    {
        return $this->indexPatterns === $other->indexPatterns
            && $this->mappings->compareTo($other->mappings)
            && $this->settings->compareTo($other->settings);
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        $mapping = $this->mappings;
        if ($this->type !== null) {
            $mapping = [$this->type => $mapping];
        }
        return [
            'index_patterns' => $this->indexPatterns,
            'mappings' => $mapping,
            'settings' => $this->settings,
        ];
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
