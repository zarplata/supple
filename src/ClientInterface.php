<?php

declare(strict_types=1);

namespace Zp\Supple;

use Zp\Supple\Elasticsearch\Index;
use Zp\Supple\Elasticsearch\IndexTemplate;
use Zp\Supple\Indexation\ChangeSet;

interface ClientInterface
{
    /**
     * @param array<ChangeSet> $changeSets
     * @return void
     */
    public function batch(array $changeSets): void;

    public function hasIndex(string $indexName): bool;

    public function getIndex(string $indexName): Index;

    public function putIndex(Index $index): void;

    public function putIndexSettings(Index $index): void;

    public function putIndexMappings(Index $index): void;

    public function openIndex(Index $index): void;

    public function closeIndex(Index $index): void;

    public function hasTemplate(string $templateName): bool;

    public function getTemplate(string $templateName): IndexTemplate;

    public function putTemplate(IndexTemplate $template): void;
}
