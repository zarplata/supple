<?php

declare(strict_types=1);

namespace Zp\Supple;

interface ConfigurationProfileInterface
{
    public function configure(ConfigurableInterface $configuration): void;
}
