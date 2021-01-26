<?php

declare(strict_types=1);

namespace Zp\Supple\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class IndexSetting
{
    /**
     * @Required
     * @var string
     */
    public $name;

    /** @var string */
    public $value;
}
