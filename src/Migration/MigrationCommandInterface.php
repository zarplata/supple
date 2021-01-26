<?php

declare(strict_types=1);

namespace Zp\Supple\Migration;

use Zp\Supple\ClientInterface;

interface MigrationCommandInterface
{
    public function execute(ClientInterface $client): void;
}
