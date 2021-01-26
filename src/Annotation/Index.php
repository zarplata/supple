<?php

declare(strict_types=1);

namespace Zp\Supple\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Index
{
    /**
     * @Required
     * @var string
     */
    public $name;
}
