<?php

declare(strict_types=1);

namespace Zp\Supple\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class IndexMapping
{
    /**
     * @Required
     * @var string
     */
    public $name;

    /** @var mixed */
    public $value;
}
