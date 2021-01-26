<?php

declare(strict_types=1);

namespace Zp\Supple\Elasticsearch;

use JsonSerializable;

use const SORT_NATURAL;

class IndexSettings implements JsonSerializable
{
    private const PROTECTED_SETTINGS = [
        'index.number_of_shards',
        'index.number_of_replicas',
        'index.routing.allocation.include._tier_preference',
    ];

    /** @var array<string, ?string> */
    private $settings;

    /**
     * @param array<string, string> $settings
     */
    public function __construct(array $settings = [])
    {
        $this->settings = self::flatten($settings);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function add(string $name, $value): void
    {
        if (is_array($value)) {
            $this->settings = array_replace($this->settings, self::flatten([$name => $value]));
        } else {
            $this->settings[$name] = $value;
        }
    }

    public function compareTo(self $other): bool
    {
        /** @noinspection TypeUnsafeComparisonInspection */
        return $this->settings == $other->settings;
    }

    public function compareNonDynamicTo(self $other): bool
    {
        /** @noinspection TypeUnsafeComparisonInspection */
        return $this->getNonDynamicSettings() == $other->getNonDynamicSettings();
    }

    public function removeNotUpdateableSettings(): self
    {
        $clone = clone $this;
        unset($clone->settings['index.number_of_shards']);
        return $clone;
    }

    public function mergeMissingSettingsFrom(self $remote): self
    {
        $clone = clone $this;
        foreach ($remote->settings as $name => $value) {
            if (array_key_exists($name, $this->settings)) {
                continue;
            }
            if (preg_match('#index\.analysis\.(\w+)\.(\w+)\.#', $name, $match)) {
                continue;
            }

            $isProtected = in_array($name, self::PROTECTED_SETTINGS, true);
            $clone->settings[$name] = $isProtected ? $value : null;
        }
        return $clone;
    }

    public function jsonSerialize(): object
    {
        $settings = $this->settings;
        ksort($settings, SORT_NATURAL);
        return (object)$settings;
    }

    /**
     * @return array<string, ?string>
     */
    private function getNonDynamicSettings(): array
    {
        $settings = array_filter(
            $this->settings,
            static function (string $key): bool {
                return strpos($key, 'analysis.') === 0;
            },
            \ARRAY_FILTER_USE_KEY
        );
        ksort($settings);
        return $settings;
    }

    /**
     * @param array<string, mixed> $items
     * @param string $prefix
     * @return array<string, mixed>
     */
    private static function flatten(array $items, string $prefix = ''): array
    {
        $flatten = [];
        $flattens = [];
        foreach ($items as $key => $value) {
            $currentPrefix = $prefix . $key;
            if (is_array($value) && !empty($value) && !isset($value[0])) {
                $flattens[] = self::flatten($value, $currentPrefix . '.');
            } else {
                $flatten[$currentPrefix] = $value;
            }
        }
        return array_replace($flatten, ...$flattens);
    }

    /**
     * @return string
     * @throws \JsonException
     */
    public function __toString(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }
}
