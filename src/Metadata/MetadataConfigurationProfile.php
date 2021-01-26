<?php

declare(strict_types=1);

namespace Zp\Supple\Metadata;

use Zp\Supple\ConfigurableInterface;
use Zp\Supple\ConfigurationProfileInterface;
use Zp\Supple\Indexation\IdentifierResolverFactory;
use Zp\Supple\SuppleConfigurationException;

class MetadataConfigurationProfile implements ConfigurationProfileInterface
{
    /** @var Metadata */
    private $metadata;

    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * @param ConfigurableInterface $configuration
     * @throws SuppleConfigurationException
     */
    public function configure(ConfigurableInterface $configuration): void
    {
        if (count($this->metadata->indices) > 0) {
            $configuration->toIndices(...$this->metadata->indices);
        }
        if ($this->metadata->templateName !== null) {
            $configuration->withIndexTemplate(
                $this->metadata->templateName,
                $this->metadata->templatePatterns
            );
        }
        foreach ($this->metadata->mappings as $name => $value) {
            $configuration->addMapping($name, $value);
        }
        foreach ($this->metadata->settings as $name => $value) {
            $configuration->addSetting($name, $value);
        }
        foreach ($this->metadata->analysis as $analysis) {
            $configuration->addAnalysis($analysis->type, $analysis->name, $analysis->definition);
        }
        if ($this->metadata->identifierProperty !== null) {
            $configuration->useDocumentID(
                IdentifierResolverFactory::createPropertyResolver($this->metadata->identifierProperty->name)
            );
        }
        foreach ($this->metadata->properties as $property) {
            if ($property->mapping === []) {
                continue;
            }
            $configuration->addMappingProperty($property->name, $property->mapping);
        }
    }
}
