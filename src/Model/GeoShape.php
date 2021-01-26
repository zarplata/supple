<?php

declare(strict_types=1);

namespace Zp\Supple\Model;

/**
 * GeoShape model
 */
class GeoShape
{
    /** @var string */
    public $type;

    /** @var array<mixed> */
    public $coordinates = [];

    /** @var string */
    public $orientation;

    /** @var array<mixed> */
    public $geometries;

    /** @var string */
    public $radius;
}
