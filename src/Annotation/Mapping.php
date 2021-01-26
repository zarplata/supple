<?php

declare(strict_types=1);

namespace Zp\Supple\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Mapping
{
    /** @var string */
    public $name;

    /**
     * @Required
     * @var string
     */
    public $type;

    /** @var string */
    public $analyzer;

    /** @var float */
    public $boost;

    /** @var bool */
    public $coerce;

    /** @var mixed */
    public $copyTo;

    /** @var bool */
    public $docValues;

    /** @var bool */
    public $dynamic;

    /** @var bool */
    public $eagerGlobalOrdinals;

    /** @var bool */
    public $enabled;

    /** @var string */
    public $format;

    /** @var int */
    public $ignoreAbove;

    /** @var bool */
    public $ignoreMalformed;

    /** @var bool */
    public $index;

    /** @var string */
    public $indexOptions;

    /** @var bool */
    public $indexPhrases;

    /** @var array<int> */
    public $indexPrefixes;

    /** @var array<mixed> */
    public $meta;

    /** @var array<mixed> */
    public $fields;

    /** @var string */
    public $normalizer;

    /** @var bool */
    public $norms;

    /** @var mixed */
    public $nullValue;

    /** @var int */
    public $positionIncrementGap;

    /** @var int */
    public $scalingFactor;

    /** @var string */
    public $searchAnalyzer;

    /** @var string */
    public $searchQuoteAnalyzer;

    /** @var string */
    public $similarity;

    /** @var bool */
    public $store;

    /** @var string */
    public $termVector;
}
