<?php

declare(strict_types=1);

namespace Zp\Supple\Migration\Command;

use Zp\Supple\ClientInterface;
use Zp\Supple\Elasticsearch\IndexTemplate;
use Zp\Supple\Migration\MigrationCommandInterface;

class PutTemplateMigrationCommand implements MigrationCommandInterface
{
    /** @var IndexTemplate */
    private $indexTemplate;

    public function __construct(IndexTemplate $indexTemplate)
    {
        $this->indexTemplate = $indexTemplate;
    }

    public function execute(ClientInterface $client): void
    {
        $client->putTemplate($this->indexTemplate);
    }
}
