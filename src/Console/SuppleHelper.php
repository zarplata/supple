<?php

namespace Zp\Supple\Console;

use Symfony\Component\Console\Helper\Helper;
use Zp\Supple\Supple;

class SuppleHelper extends Helper
{
    /** @var Supple */
    protected $supple;

    public function __construct(Supple $supple)
    {
        $this->supple = $supple;
    }

    public function getSupple(): Supple
    {
        return $this->supple;
    }

    public function getName(): string
    {
        return 'supple';
    }
}
