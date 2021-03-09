<?php

declare(strict_types=1);

namespace Zp\Supple\Client;

use Elasticsearch\Client as ElasticsearchClient;
use Zp\Supple\ClientInterface;
use Zp\Supple\Elasticsearch\Index;
use Zp\Supple\Elasticsearch\IndexMappings;
use Zp\Supple\Elasticsearch\IndexSettings;
use Zp\Supple\Elasticsearch\IndexTemplate;
use Zp\Supple\Indexation\IndexationError;
use Zp\Supple\Indexation\IndexationResult;

class Client implements ClientInterface
{
    private const TYPE = '_doc';

    /** @var ElasticsearchClient */
    private $client;

    /** @var bool */
    private $hasMappingType;

    public function __construct(ElasticsearchClient $client)
    {
        $this->client = $client;
        $this->hasMappingType = (bool)version_compare('7.0.0', $client::VERSION, '>');
    }

    /**
     * @inheritDoc
     */
    public function batch(array $changeSets): void
    {
        $ordered = [];
        $body = [];
        foreach ($changeSets as $changeSet) {
            foreach ($changeSet->getIndexTo() as $index) {
                $metadata = ['_index' => $index->getName()];
                if ($this->hasMappingType) {
                    $metadata['_type'] = self::TYPE;
                }
                if ($changeSet->getID()) {
                    $metadata['_id'] = $changeSet->getID();
                }

                $body[] = ['index' => $metadata];
                $body[] = $changeSet->getSource();
                $ordered[] = $changeSet;
            }

            foreach ($changeSet->getDeleteFrom() as $index) {
                $metadata = ['_index' => $index->getName()];
                if ($this->hasMappingType) {
                    $metadata['_type'] = self::TYPE;
                }
                $metadata['_id'] = $changeSet->getID();

                $body[] = ['delete' => $metadata];
                $ordered[] = $changeSet;
            }
        }

        $response = $this->client->bulk(['body' => $body]);
        foreach ($response['items'] as $i => $item) {
            $action = array_key_first($item);
            $result = $item[$action];
            if (isset($result['error'])) {
                $error = IndexationError::create($action, $result['_index'], $result['_id'], $result['error']);
                $ordered[$i]->addError($error);
            }
        }
    }

    public function hasIndex(string $indexName): bool
    {
        return $this->client->indices()->exists(['index' => $indexName]);
    }

    public function getIndex(string $indexName): Index
    {
        $response = $this->client->indices()->get(['index' => $indexName]);
        $rawIndex = $response[$indexName];
        $rawSettings = $rawIndex['settings'];
        unset(
            $rawSettings['index']['provided_name'],
            $rawSettings['index']['creation_date'],
            $rawSettings['index']['uuid'],
            $rawSettings['index']['version'],
        );
        return new Index(
            $indexName,
            new IndexMappings($this->removeMappingType($rawIndex['mappings'])),
            new IndexSettings($rawSettings)
        );
    }

    public function putIndex(Index $index): void
    {
        if ($this->hasMappingType) {
            $index->setMappingType(self::TYPE);
        }
        $this->client->indices()->create(
            [
                'index' => $index->getName(),
                'body' => $index,
            ]
        );
    }

    public function putIndexSettings(Index $index): void
    {
        $this->client->indices()->putSettings(
            [
                'index' => $index->getName(),
                'body' => $index->getSettings(),
            ]
        );
    }

    public function putIndexMappings(Index $index): void
    {
        $this->client->indices()->putMapping(
            [
                'index' => $index->getName(),
                'body' => $index->getMappings(),
            ]
        );
    }

    public function openIndex(Index $index): void
    {
        $this->client->indices()->open(['index' => $index->getName()]);
    }

    public function closeIndex(Index $index): void
    {
        $this->client->indices()->close(['index' => $index->getName()]);
    }

    public function hasTemplate(string $templateName): bool
    {
        return $this->client->indices()->existsTemplate(['name' => $templateName]);
    }

    public function getTemplate(string $templateName): IndexTemplate
    {
        $response = $this->client->indices()->getTemplate(['name' => $templateName, 'flat_settings' => true]);
        $rawTemplate = $response[$templateName];

        $template = new IndexTemplate(
            $templateName,
            $rawTemplate['index_patterns'] ?? [],
            new IndexMappings($this->removeMappingType($rawTemplate['mappings'])),
            new IndexSettings($rawTemplate['settings'])
        );
        if ($this->hasMappingType) {
            $template->setType(self::TYPE);
        }
        return $template;
    }

    public function putTemplate(IndexTemplate $template): void
    {
        if ($this->hasMappingType) {
            $template->setType(self::TYPE);
        }
        $request = [
            'name' => $template->getName(),
            'body' => $template,
        ];
        $this->client->indices()->putTemplate($request);
    }

    /**
     * @param array<mixed> $rawMapping
     * @return array<mixed>
     */
    private function removeMappingType(array $rawMapping): array
    {
        return $this->hasMappingType ? ($rawMapping[self::TYPE] ?? []) : $rawMapping;
    }
}
