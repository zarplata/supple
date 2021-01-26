<?php

declare(strict_types=1);

namespace Zp\Supple;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
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

    /** @var ?Serializer */
    private $serializer;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function useClient(ClientInterface $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function useElasticsearchClient(\Elasticsearch\Client $client, ?bool $hasMappingType): self
    {
        $this->client = new Client(
            $client,
            $hasMappingType ?? (bool)version_compare('7.0.0', $client::VERSION, '>')
        );
        return $this;
    }

    public function setAnnotationReader(Reader $reader): self
    {
        $this->annotationReader = $reader;
        return $this;
    }

    public function setSerializer(Serializer $serializer): self
    {
        $this->serializer = $serializer;
        return $this;
    }

    public function setNamingStrategy(NamingStrategyInterface $strategy): self
    {
        $this->namingStrategy = $strategy;
        return $this;
    }

    public function build(): Supple
    {
        if ($this->client === null) {
            throw new SuppleException(
                'elasticsearch client must be provided via useClient or useElasticsearchClient method'
            );
        }

        $annotationReader = $this->annotationReader ?: new AnnotationReader();
        $namingStrategy = $this->namingStrategy ?: new CamelCaseNamingStrategy();
        $serializer = $this->serializer ?: SerializerBuilder::create()
            ->setAnnotationReader($annotationReader)
            ->setPropertyNamingStrategy($namingStrategy->getSerializerNameStrategy())
            ->setSerializationContextFactory(function () {
                return SerializationContext::create()->setSerializeNull(true);
            })
            ->build();

        return new Supple(
            $this->client,
            new MetadataFactory($annotationReader, $namingStrategy),
            new Indexer($this->client, $serializer),
        );
    }
}
