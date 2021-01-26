<?php

declare(strict_types=1);

namespace Zp\Supple\Metadata;

class MetadataProperty
{
    /** @var string */
    public $name;

    /** @var bool */
    public $isIdentifier = false;

    /** @var array<string, mixed> */
    public $mapping = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
