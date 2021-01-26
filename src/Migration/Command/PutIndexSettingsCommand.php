<?php

declare(strict_types=1);

namespace Zp\Supple\Migration\Command;

use Zp\Supple\ClientInterface;
use Zp\Supple\Elasticsearch\Index;
use Zp\Supple\Migration\MigrationCommandInterface;

class PutIndexSettingsCommand implements MigrationCommandInterface
{
    /** @var Index */
    private $index;

    public function __construct(Index $index)
    {
        $this->index = $index;
    }

    public function wrapCloseIndex(): MigrationCommandInterface
    {
        return new CloseIndexMigrationCommand($this->index, $this);
    }

    public function execute(ClientInterface $client): void
    {
        $client->putIndexSettings($this->index);
    }
}
