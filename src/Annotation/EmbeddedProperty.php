<?php

declare(strict_types=1);

namespace Zp\Supple\Annotation;

/**
 * @Annotation
 * @Target({"CLASS", "PROPERTY"})
 */
class EmbeddedProperty extends Property
{
    /** @var string */
    public $type = '';

    /**
     * @Required
     * @psalm-var class-string
     * @var string
     */
    public $targetClass;
}
