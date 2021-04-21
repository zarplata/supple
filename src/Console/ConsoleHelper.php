<?php

declare(strict_types=1);

namespace Zp\Supple\Console;

use Symfony\Component\Console\Helper\Helper;
use Zp\Supple\Supple;

class ConsoleHelper extends Helper
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
