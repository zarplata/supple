<?php

declare(strict_types=1);

namespace Zp\Supple\Migration\Command;

use Zp\Supple\ClientInterface;
use Zp\Supple\Elasticsearch\Index;
use Zp\Supple\Migration\MigrationCommandInterface;

class PutIndexMappingsMigrationCommand implements MigrationCommandInterface
{
    /** @var Index */
    private $index;

    public function __construct(Index $index)
    {
        $this->index = $index;
    }

    public function execute(ClientInterface $client): void
    {
        $client->putIndexMappings($this->index);
    }
}
