<?php

declare(strict_types=1);

namespace Zp\Supple\Migration\Command;

use Zp\Supple\ClientInterface;
use Zp\Supple\Elasticsearch\Index;
use Zp\Supple\Migration\MigrationCommandInterface;

class CloseIndexMigrationCommand implements MigrationCommandInterface
{
    /** @var Index */
    private $index;

    /** @var MigrationCommandInterface */
    private $command;

    public function __construct(Index $index, MigrationCommandInterface $command)
    {
        $this->index = $index;
        $this->command = $command;
    }

    public function execute(ClientInterface $client): void
    {
        $client->closeIndex($this->index);
        try {
            $this->command->execute($client);
        } finally {
            $client->openIndex($this->index);
        }
    }
}
