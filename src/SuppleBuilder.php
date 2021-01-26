<?php

declare(strict_types=1);

namespace Zp\Supple;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use JMS\Serializer\SerializerBuilder;
use Zp\Supple\Client\Client;
use Zp\Supple\Indexation\Indexer;
use Zp\Supple\Metadata\MetadataFactory;
use Zp\Supple\Naming\CamelCaseNamingStrategy;

class SuppleBuilder
{
    /** @var ClientInterface */
    private $client;

    /** @var ?NamingStrategyInterface */
    private $namingStrategy;

    /** @var ?Reader */
    private $annotationReader;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    /**
     * @param \Zp\Supple\ClientInterface $client
     * @return $this
     */
    public function useClient(ClientInterface $client): self
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @param \Elasticsearch\Client $client
     * @return $this
     */
    public function useElasticsearchClient(\Elasticsearch\Client $client): self
    {
        $this->client = new Client($client);
        return $this;
    }

    /**
     * @param \Doctrine\Common\Annotations\Reader $reader
     * @return $this
     */
    public function setAnnotationReader(Reader $reader): self
    {
        $this->annotationReader = $reader;
        return $this;
    }

    /**
     * @param NamingStrategyInterface $strategy
     * @return $this
     */
    public function setNamingStrategy(NamingStrategyInterface $strategy): self
    {
        $this->namingStrategy = $strategy;
        return $this;
    }

    /**
     * @return \Zp\Supple\Supple
     * @throws \Zp\Supple\SuppleException
     */
    public function build(): Supple
    {
        if ($this->client === null) {
            throw new SuppleException(
                'elasticsearch client must be provided via useClient or useElasticsearchClient method'
            );
        }

        $annotationReader = $this->annotationReader ?: new AnnotationReader();
        $namingStrategy = $this->namingStrategy ?: new CamelCaseNamingStrategy();

        $serializer = SerializerBuilder::create()
            ->setAnnotationReader($annotationReader)
            ->setPropertyNamingStrategy($namingStrategy->getSerializerNameStrategy())
            ->build();

        return new Supple(
            $this->client,
            new MetadataFactory($annotationReader, $namingStrategy),
            new Indexer($this->client, $serializer),
        );
    }
}
