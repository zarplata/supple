<?php

declare(strict_types=1);

namespace Zp\Supple\Metadata;

use Zp\Supple\Annotation\IndexAnalysis;

class Metadata
{
    /**
     * @template T of object
     * @var class-string<T>
     */
    public $className;

    /** @var array<string> */
    public $indices = [];

    /** @var array<string> */
    public $clients = [];

    /** @var ?string */
    public $templateName;

    /** @var array<string> */
    public $templatePatterns = [];

    /** @var ?MetadataProperty */
    public $identifierProperty;

    /** @var array<MetadataProperty> */
    public $properties = [];

    /** @var array<string, array<mixed>> */
    public $mappings = [];

    /** @var array<string, mixed> */
    public $settings = [];

    /** @var array<IndexAnalysis> */
    public $analysis = [];

    /**
     * @param class-string $className
     */
    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function __toString(): string
    {
        return $this->className;
    }
}
