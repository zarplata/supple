<?php

declare(strict_types=1);

namespace Zp\Supple\Model;

/**
 * GeoPoint model
 */
class GeoPoint
{
    /** @var float */
    public $lat;

    /** @var float */
    public $lon;

    public function __construct(float $lat = 0.0, float $lon = 0.0)
    {
        $this->lat = $lat;
        $this->lon = $lon;
    }
}
