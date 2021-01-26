<?php

declare(strict_types=1);

namespace Zp\Supple\Configuration;

use Zp\Supple\ConfigurableInterface;
use Zp\Supple\Elasticsearch\Index;
use Zp\Supple\Elasticsearch\IndexMappings;
use Zp\Supple\Elasticsearch\IndexSettings;
use Zp\Supple\Elasticsearch\IndexTemplate;
use Zp\Supple\Indexation\IdentifierResolverFactory;
use Zp\Supple\Indexation\IdentifierResolverInterface;
use Zp\Supple\Indexation\RouterFactory;
use Zp\Supple\Indexation\RouterInterface;
use Zp\Supple\SuppleConfigurationException;

class Configuration implements ConfigurableInterface
{
    /** @var string */
    private $name;

    /** @var ?callable():array<Index> */
    private $indices;

    /** @var ?string */
    private $templateName;

    /** @var array<string> */
    private $templatePattern = [];

    /** @var \Zp\Supple\Elasticsearch\IndexMappings */
    private $mappings;

    /** @var \Zp\Supple\Elasticsearch\IndexSettings */
    private $settings;

    /** @var RouterInterface */
    private $documentRouter;

    /** @var IdentifierResolverInterface */
    private $documentIdentifierResolver;

    /** @var bool */
    private $migrateExistentIndices = false;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->settings = new IndexSettings();
        $this->mappings = new IndexMappings();
        $this->useDocumentID(IdentifierResolverFactory::createEmptyResolver())
            ->useIndexRouter(RouterFactory::createFanout());
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<Index>
     */
    public function getIndices(): array
    {
        if ($this->indices === null) {
            return [];
        }
        return array_map(
            function (string $name) {
                return new Index($name, $this->mappings, $this->settings);
            },
            ($this->indices)()
        );
    }

    public function hasTemplate(): bool
    {
        return $this->templateName !== null;
    }

    /**
     * @return ?IndexTemplate
     */
    public function getTemplate(): ?IndexTemplate
    {
        if ($this->templateName === null) {
            return null;
        }
        return new IndexTemplate($this->templateName, $this->templatePattern, $this->mappings, $this->settings);
    }

    public function getMappings(): IndexMappings
    {
        return $this->mappings;
    }

    public function getSettings(): IndexSettings
    {
        return $this->settings;
    }

    public function getIndexRouter(): RouterInterface
    {
        return $this->documentRouter;
    }

    public function getDocumentIdentifierResolver(): IdentifierResolverInterface
    {
        return $this->documentIdentifierResolver;
    }

    public function toIndices(string ...$indices): ConfigurableInterface
    {
        $this->toDynamicIndices(
            function () use ($indices): array {
                return $indices;
            }
        );
        return $this;
    }

    public function toDynamicIndices(callable $indices): ConfigurableInterface
    {
        $this->indices = $indices;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withIndexTemplate(string $name, array $pattern): ConfigurableInterface
    {
        $this->templateName = $name;
        $this->templatePattern = $pattern;
        return $this;
    }

    /**
     * @param IdentifierResolverInterface $resolver
     * @return $this
     */
    public function useDocumentID(IdentifierResolverInterface $resolver): ConfigurableInterface
    {
        $this->documentIdentifierResolver = $resolver;
        return $this;
    }

    /**
     * @param RouterInterface $router
     * @return $this
     */
    public function useIndexRouter(RouterInterface $router): ConfigurableInterface
    {
        $this->documentRouter = $router;
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function addMapping(string $name, $value): ConfigurableInterface
    {
        $this->mappings->add($name, $value);
        return $this;
    }

    /**
     * @param string $name
     * @param array<string, mixed> $definition
     * @return $this
     */
    public function addMappingProperty(string $name, array $definition): ConfigurableInterface
    {
        $this->mappings->addProperty($name, $definition);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addSetting(string $name, $value): ConfigurableInterface
    {
        $this->settings->add($name, $value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addAnalysis(string $type, string $name, array $definition): ConfigurableInterface
    {
        $this->settings->add(sprintf('index.analysis.%s.%s', $type, $name), $definition);

        return $this;
    }
}
