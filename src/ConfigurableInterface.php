<?php

declare(strict_types=1);

namespace Zp\Supple;

use Zp\Supple\Indexation\IdentifierResolverInterface;
use Zp\Supple\Indexation\RouterInterface;

interface ConfigurableInterface
{
    /**
     * @param string ...$indices
     * @return $this
     * @throws SuppleConfigurationException
     */
    public function toIndices(string ...$indices): self;

    /**
     * @param callable():array<string> $indices
     * @return $this
     * @throws SuppleConfigurationException
     */
    public function toDynamicIndices(callable $indices): self;

    /**
     * @param string $name
     * @param array<string> $pattern
     * @return $this
     * @throws SuppleConfigurationException
     */
    public function withIndexTemplate(string $name, array $pattern): self;

    /**
     * @param IdentifierResolverInterface $resolver
     * @return $this
     */
    public function useDocumentID(IdentifierResolverInterface $resolver): self;

    /**
     * @param RouterInterface $router
     * @return $this
     */
    public function useIndexRouter(RouterInterface $router): self;

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     * @throws SuppleConfigurationException
     */
    public function addMapping(string $name, $value): self;

    /**
     * @param string $name
     * @param array<string, mixed> $definition
     * @return $this
     * @throws SuppleConfigurationException
     */
    public function addMappingProperty(string $name, array $definition): self;

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     * @throws SuppleConfigurationException
     */
    public function addSetting(string $name, $value): self;

    /**
     * @param string $type
     * @param string $name
     * @param array<mixed> $definition
     * @return $this
     */
    public function addAnalysis(string $type, string $name, array $definition): self;
}
