<?php

declare(strict_types=1);

namespace Zp\Supple\Elasticsearch;

use JsonSerializable;

final class Index implements JsonSerializable
{
    /** @var string */
    private $name;

    /** @var string */
    private $type = '';

    /** @var IndexMappings */
    private $mappings;

    /** @var IndexSettings */
    private $settings;

    public function __construct(string $name, IndexMappings $mappings, IndexSettings $settings)
    {
        $this->name = $name;
        $this->mappings = $mappings;
        $this->settings = $settings;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function matchNameTo(string $pattern): bool
    {
        return fnmatch($pattern, $this->name);
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getMappings(): IndexMappings
    {
        return $this->mappings;
    }

    public function getSettings(): IndexSettings
    {
        return $this->settings;
    }

    public function compareMappingsTo(Index $other): bool
    {
        return $this->mappings->compareTo($other->mappings);
    }

    public function compareSettingsTo(Index $other): bool
    {
        return $this->settings->compareTo($other->settings);
    }

    public function compareNonDynamicSettingsTo(Index $other): bool
    {
        return $this->settings->compareNonDynamicTo($other->settings);
    }

    public function mergeMissingSettingsFrom(Index $other): self
    {
        $clone = clone $this;
        $clone->settings = $this->settings->mergeMissingSettingsFrom($other->settings);
        return $clone;
    }

    public function copyTypeFrom(Index $other): self
    {
        $clone = clone $this;
        $clone->mappings->setType($other->mappings->getType());
        return $this;
    }

    public function removeNotUpdateableSettings(): self
    {
        $clone = clone $this;
        $clone->settings = $this->settings->removeNotUpdateableSettings();
        return $clone;
    }

    public function jsonSerialize(): object
    {
        $mappings = $this->type
            ? [$this->type => $this->mappings]
            : $this->mappings;

        return (object)[
            'mappings' => $mappings,
            'settings' => $this->settings,
        ];
    }

    public function __clone()
    {
        $this->mappings = clone $this->mappings;
        $this->settings = clone $this->settings;
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
