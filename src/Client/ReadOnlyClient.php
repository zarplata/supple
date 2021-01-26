<?php

declare(strict_types=1);

namespace Zp\Supple\Client;

use Zp\Supple\ClientInterface;
use Zp\Supple\Elasticsearch\Index;
use Zp\Supple\Elasticsearch\IndexTemplate;

class ReadOnlyClient implements ClientInterface
{
    /** @var ClientInterface */
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @inheritDoc
     */
    public function batch(array $changeSets): void
    {
        // nop
    }

    public function hasIndex(string $indexName): bool
    {
        return $this->client->hasIndex($indexName);
    }

    public function getIndex(string $indexName): Index
    {
        return $this->client->getIndex($indexName);
    }

    public function putIndex(Index $index): void
    {
        // nop
    }

    public function putIndexSettings(Index $index): void
    {
        // nop
    }

    public function putIndexMappings(Index $index): void
    {
        // nop
    }

    public function openIndex(Index $index): void
    {
        // nop
    }

    public function closeIndex(Index $index): void
    {
        // nop
    }

    public function hasTemplate(string $templateName): bool
    {
        return $this->client->hasTemplate($templateName);
    }

    public function getTemplate(string $templateName): IndexTemplate
    {
        return $this->client->getTemplate($templateName);
    }

    public function putTemplate(IndexTemplate $template): void
    {
        // nop
    }
}
