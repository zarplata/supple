<?php

declare(strict_types=1);

namespace Zp\Supple\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class IndexAnalysis
{
    /**
     * @Required
     * @Enum({"analyzer","tokenizer","char_filter","filter"})
     * @var string
     */
    public $type;

    /**
     * @Required
     * @var string
     */
    public $name;

    /**
     * @Required
     * @var array<mixed>
     */
    public $definition;
}
