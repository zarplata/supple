<?php

declare(strict_types=1);

namespace Zp\Supple\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class EmbeddedMapping extends Mapping
{
    /**
     * @Required
     * @psalm-var class-string
     * @var string
     */
    public $targetClass;
}
