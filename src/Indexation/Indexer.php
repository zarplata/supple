<?php

declare(strict_types=1);

namespace Zp\Supple\Indexation;

use JMS\Serializer\SerializerInterface;
use Zp\Supple\ClientInterface;
use Zp\Supple\Configuration\Configuration;

class Indexer
{
    /** @var SerializerInterface */
    private $serializer;

    /** @var UnitOfWork */
    private $unitOfWork;

    public function __construct(ClientInterface $client, SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
        $this->unitOfWork = new UnitOfWork($client);
    }

    /**
     * Index document to Elasticsearch
     *
     * @param Configuration $configuration
     * @param object $document
     */
    public function index(Configuration $configuration, object $document): void
    {
        $id = $configuration->getDocumentIdentifierResolver()->resolve($document);
        $source = $this->serializer->serialize($document, 'json');
        $changeSet = new ChangeSet($id, $document, $source);
        foreach ($configuration->getIndices() as $index) {
            $configuration->getIndexRouter()->route($document, $index, $changeSet);
        }
        $this->unitOfWork->add($changeSet);
    }

    /**
     * Delete document from Elasticsearch
     *
     * @param Configuration $configuration
     * @param string $id
     * @throws IndexationException
     */
    public function delete(Configuration $configuration, string $id): void
    {
        $changeSet = new ChangeSet($id, null, null);
        foreach ($configuration->getIndices() as $index) {
            $changeSet->deleteFrom($index);
        }
        $this->unitOfWork->add($changeSet);
    }

    /**
     * Flush indexation unit of work
     *
     * @return IndexationResult
     */
    public function flush(): IndexationResult
    {
        $changeSets = $this->unitOfWork->commit();
        return new IndexationResult($changeSets);
    }

    /**
     * Clear indexation unit of work
     *
     * @return void
     */
    public function clear(): void
    {
        $this->unitOfWork->clear();
    }
}
