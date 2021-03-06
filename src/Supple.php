<?php

declare(strict_types=1);

namespace Zp\Supple;

use Zp\Supple\Client\ReadOnlyClient;
use Zp\Supple\Configuration\Configuration;
use Zp\Supple\Configuration\ConfigurationValidator;
use Zp\Supple\Generator\CodeGenerator;
use Zp\Supple\Indexation\IndexationResult;
use Zp\Supple\Indexation\Indexer;
use Zp\Supple\Configuration\MetadataConfiguration;
use Zp\Supple\Metadata\MetadataFactory;
use Zp\Supple\Migration\MigrationRunner;

class Supple
{
    /** @var ClientInterface */
    private $client;

    /** @var MetadataFactory */
    private $metadataFactory;

    /** @var Indexer */
    private $indexer;

    /** @var array<string, Configuration> */
    private $configurations = [];

    public function __construct(ClientInterface $client, MetadataFactory $metadataFactory, Indexer $indexer)
    {
        $this->client = $client;
        $this->metadataFactory = $metadataFactory;
        $this->indexer = $indexer;
    }

    /**
     * Register and configure document mappings and settings.
     *
     * @param class-string $documentClass
     * @param ConfigurationProfileInterface ...$profiles
     * @return ConfigurableInterface
     * @throws SuppleConfigurationException
     * @throws \ReflectionException
     */
    public function registerDocument(
        string $documentClass,
        ConfigurationProfileInterface ...$profiles
    ): ConfigurableInterface {
        $metadata = $this->metadataFactory->create($documentClass);

        $configuration = new Configuration($documentClass);
        $configuration->configure(
            new MetadataConfiguration($metadata),
            ...$profiles
        );

        $this->configurations[$documentClass] = $configuration;
        return $configuration;
    }

    public function configuration(string $configurationName): Configuration
    {
        if (!array_key_exists($configurationName, $this->configurations)) {
            throw new SuppleConfigurationException(sprintf('configuration %s not found', $configurationName));
        }
        return $this->configurations[$configurationName];
    }

    /**
     * Index document.
     *
     * @param object $document
     * @throws Indexation\IndexationException
     */
    public function index(object $document): void
    {
        $this->indexTo(get_class($document), $document);
    }

    /**
     * Index document to specific configuration.
     *
     * @param string $configurationName
     * @param object $document
     * @throws Indexation\IndexationException
     */
    public function indexTo(string $configurationName, object $document): void
    {
        $this->indexer->index($this->configuration($configurationName), $document);
    }

    /**
     * Delete document.
     *
     * @param string $configurationName
     * @param string $id
     * @throws Indexation\IndexationException
     */
    public function deleteFrom(string $configurationName, string $id): void
    {
        $configuration = $this->configuration($configurationName);
        $this->indexer->delete($configuration, $id);
    }

    /**
     * Flush transaction.
     *
     * @return IndexationResult
     */
    public function flush(): IndexationResult
    {
        return $this->indexer->flush();
    }

    /**
     * Run migrator.
     *
     * @param bool $dryRun
     * @return MigrationRunner
     * @throws SuppleConfigurationException
     */
    public function migrate(bool $dryRun = false): MigrationRunner
    {
        $client = $dryRun ? new ReadOnlyClient($this->client) : $this->client;

        return new MigrationRunner(
            $client,
            $this->configurations
        );
    }

    /**
     * Run code generator.
     *
     * @return CodeGenerator
     */
    public function generateCode(): CodeGenerator
    {
        return new CodeGenerator($this->client);
    }
}
