<?php

declare(strict_types=1);

namespace Zp\Supple\Annotation;

/**
 * @Annotation
 * @Target({"CLASS", "PROPERTY"})
 */
class Property
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

    /** @var int */
    public $dims;

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

    /** @var bool */
    public $fielddata;

    /** @var mixed */
    public $fields;

    /** @var string */
    public $normalizer;

    /** @var bool */
    public $norms;

    /** @var mixed */
    public $nullValue;

    /** @var string */
    public $path;

    /** @var int */
    public $positionIncrementGap;

    /** @var array */
    public $properties = [];

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
