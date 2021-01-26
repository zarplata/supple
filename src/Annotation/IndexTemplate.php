<?php

declare(strict_types=1);

namespace Zp\Supple\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class IndexTemplate
{
    /**
     * @Required
     * @var string
     */
    public $name;

    /**
     * @Required
     * @var array<string>
     */
    public $patterns;
}
