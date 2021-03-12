<?php

declare(strict_types=1);

namespace Zp\Supple\Metadata;

use Doctrine\Common\Annotations\Reader;
use ReflectionClass;
use ReflectionException;
use Throwable;
use Zp\Supple\Annotation;
use Zp\Supple\NamingStrategyInterface;
use Zp\Supple\SuppleConfigurationException;

class MetadataFactory
{
    /** @var Reader */
    private $annotationsReader;

    /** @var array<Metadata> */
    private $storage = [];

    /** @var NamingStrategyInterface */
    private $namingStrategy;

    public function __construct(Reader $annotationReader, NamingStrategyInterface $namingStrategy)
    {
        $this->annotationsReader = $annotationReader;
        $this->namingStrategy = $namingStrategy;
    }

    /**
     * @param class-string $className
     * @return Metadata
     * @throws MetadataException
     * @throws ReflectionException
     */
    public function create(string $className): Metadata
    {
        if (array_key_exists($className, $this->storage)) {
            return $this->storage[$className];
        }

        $reflector = new ReflectionClass($className);
        $metadata = new Metadata($reflector->getName());
        $this->storage[$className] = $metadata;

        try {
            $this->parseClassAnnotations($metadata, $reflector);
            $this->parsePropertiesAnnotations($metadata, $reflector);
        } catch (Throwable $e) {
            throw new MetadataException(sprintf('class %s has incorrect annotations', $className), 0, $e);
        }

        return $metadata;
    }

    /**
     * @param Metadata $metadata
     * @param \ReflectionClass<object> $reflector
     * @throws \Zp\Supple\Metadata\MetadataException
     * @throws \Zp\Supple\SuppleConfigurationException
     */
    private function parseClassAnnotations(Metadata $metadata, ReflectionClass $reflector): void
    {
        $annotations = $this->annotationsReader->getClassAnnotations($reflector);
        self::parseAnyAnnotations(
            $annotations,
            Annotation\Index::class,
            static function (Annotation\Index $annotation) use ($metadata): void {
                $metadata->indices[] = $annotation->name;
            }
        );
        self::parseExactlyOneAnnotation(
            $annotations,
            Annotation\IndexTemplate::class,
            static function (Annotation\IndexTemplate $annotation) use ($metadata): void {
                $metadata->templateName = $annotation->name;
                $metadata->templatePatterns = $annotation->patterns;
            }
        );
        self::parseAnyAnnotations(
            $annotations,
            Annotation\IndexMapping::class,
            static function (Annotation\IndexMapping $annotation) use ($metadata): void {
                $metadata->mappings[$annotation->name] = $annotation->value;
            }
        );
        self::parseAnyAnnotations(
            $annotations,
            Annotation\IndexSetting::class,
            static function (Annotation\IndexSetting $annotation) use ($metadata): void {
                $metadata->settings[$annotation->name] = $annotation->value;
            }
        );
        self::parseAnyAnnotations(
            $annotations,
            Annotation\IndexAnalysis::class,
            static function (Annotation\IndexAnalysis $annotation) use ($metadata): void {
                $metadata->analysis[] = $annotation;
            }
        );
        self::parseAnyAnnotations(
            $annotations,
            Annotation\Property::class,
            function (Annotation\Property $annotation) use ($metadata): void {
                if ($annotation->name === '') {
                    throw new MetadataException(
                        sprintf('please provide name of class %s property', $metadata->className)
                    );
                }
                $property = new MetadataProperty($annotation->name);
                $this->populateProperty($annotation, $property);
                $metadata->properties[$property->name] = $property;
            }
        );
    }

    /**
     * @param Metadata $metadata
     * @param ReflectionClass<object> $classReflector
     * @throws SuppleConfigurationException
     */
    private function parsePropertiesAnnotations(Metadata $metadata, ReflectionClass $classReflector): void
    {
        foreach ($classReflector->getProperties() as $reflector) {
            $property = new MetadataProperty($this->namingStrategy->translate($reflector->getName()));
            $annotations = $this->annotationsReader->getPropertyAnnotations($reflector);

            self::parseExactlyOneAnnotation(
                $annotations,
                Annotation\Property::class,
                function (Annotation\Property $annotation) use ($property): void {
                    $this->populateProperty($annotation, $property);
                }
            );

            self::parseExactlyOneAnnotation(
                $annotations,
                Annotation\ID::class,
                static function () use ($metadata, $property): void {
                    $metadata->identifierProperty = $property;
                    $property->isIdentifier = true;
                }
            );

            if ($property->mapping === [] && !self::hasAnnotation($annotations, Annotation\Ignore::class)) {
                throw new MetadataException(
                    sprintf('property %s must have mapping or ignore annotation', $property->name)
                );
            }

            $metadata->properties[$property->name] = $property;
        }
    }

    /**
     * @template T
     * @param array<object> $annotations
     * @param class-string<T> $className
     * @return array<T>
     */
    private static function getAnnotations(array $annotations, string $className): array
    {
        return array_values(
            array_filter(
                $annotations,
                static function (object $annotation) use ($className): bool {
                    return $annotation instanceof $className;
                }
            )
        );
    }

    /**
     * @param array<object> $annotations
     * @param class-string $className
     * @return bool
     * @throws SuppleConfigurationException
     */
    private static function hasAnnotation(array $annotations, string $className): bool
    {
        return count(self::getAnnotations($annotations, $className)) > 0;
    }

    /**
     * @template T
     * @param array<object> $annotations
     * @param class-string<T> $className
     * @param callable(T):void $callable
     */
    private static function parseAnyAnnotations(array $annotations, string $className, callable $callable): void
    {
        array_map($callable, self::getAnnotations($annotations, $className));
    }

    /**
     * @template T
     * @param array<object> $annotations
     * @param class-string<T> $className
     * @param callable(T):void $callable
     * @throws MetadataException
     */
    private static function parseExactlyOneAnnotation(array $annotations, string $className, callable $callable): void
    {
        $found = self::getAnnotations($annotations, $className);
        switch (count($found)) {
            case 1:
                $callable($found[0]);
                return;
            case 0:
                return;
            default:
                throw new MetadataException(sprintf('annotation %s must used once', $className));
        }
    }

    private function populateProperty(Annotation\Property $annotation, MetadataProperty $property): void
    {
        if ($annotation->name !== null) {
            $property->name = $annotation->name;
        }

        if ($annotation->type) {
            $property->mapping['type'] = $annotation->type;
        }

        if ($annotation instanceof Annotation\EmbeddedProperty) {
            $targetMetadata = $this->create($annotation->targetClass);

            $property->mapping['properties'] = array_replace(
                $annotation->properties,
                array_map(
                    static function (MetadataProperty $property) {
                        return $property->mapping;
                    },
                    $targetMetadata->properties
                )
            );
        } elseif (count($annotation->properties) > 0) {
            $property->mapping['properties'] = $annotation->properties;
        }

        if ($annotation->analyzer !== null) {
            $property->mapping['analyzer'] = $annotation->analyzer;
        }
        if ($annotation->boost !== null) {
            $property->mapping['boost'] = $annotation->boost;
        }
        if ($annotation->coerce !== null) {
            $property->mapping['coerce'] = $annotation->coerce;
        }
        if ($annotation->copyTo !== null) {
            $property->mapping['copy_to'] = $annotation->copyTo;
        }
        if ($annotation->docValues !== null) {
            $property->mapping['doc_values'] = $annotation->docValues;
        }
        if ($annotation->dynamic !== null) {
            $property->mapping['dynamic'] = $annotation->dynamic;
        }
        if ($annotation->eagerGlobalOrdinals !== null) {
            $property->mapping['eager_global_ordinals'] = $annotation->eagerGlobalOrdinals;
        }
        if ($annotation->enabled !== null) {
            $property->mapping['enabled'] = $annotation->enabled;
        }
        if ($annotation->format !== null) {
            $property->mapping['format'] = $annotation->format;
        }
        if ($annotation->ignoreAbove !== null) {
            $property->mapping['ignore_above'] = $annotation->ignoreAbove;
        }
        if ($annotation->ignoreMalformed !== null) {
            $property->mapping['ignore_malformed'] = $annotation->ignoreMalformed;
        }
        if ($annotation->index !== null) {
            $property->mapping['index'] = $annotation->index;
        }
        if ($annotation->indexOptions !== null) {
            $property->mapping['index_options'] = $annotation->indexOptions;
        }
        if ($annotation->indexPhrases !== null) {
            $property->mapping['index_phrases'] = $annotation->indexPhrases;
        }
        if ($annotation->indexPrefixes !== null) {
            $property->mapping['index_prefixes'] = $annotation->indexPrefixes;
        }
        if ($annotation->meta !== null) {
            $property->mapping['meta'] = $annotation->meta;
        }
        if ($annotation->fields !== null) {
            $property->mapping['fields'] = $annotation->fields;
        }
        if ($annotation->normalizer !== null) {
            $property->mapping['normalizer'] = $annotation->normalizer;
        }
        if ($annotation->norms !== null) {
            $property->mapping['norms'] = $annotation->norms;
        }
        if ($annotation->nullValue !== null) {
            $property->mapping['null_value'] = $annotation->nullValue;
        }
        if ($annotation->path !== null) {
            $property->mapping['path'] = $annotation->path;
        }
        if ($annotation->positionIncrementGap !== null) {
            $property->mapping['position_increment_gap'] = $annotation->positionIncrementGap;
        }
        if ($annotation->scalingFactor !== null) {
            $property->mapping['scaling_factor'] = $annotation->scalingFactor;
        }
        if ($annotation->searchAnalyzer !== null) {
            $property->mapping['search_analyzer'] = $annotation->searchAnalyzer;
        }
        if ($annotation->searchQuoteAnalyzer !== null) {
            $property->mapping['search_quote_analyzer'] = $annotation->searchQuoteAnalyzer;
        }
        if ($annotation->similarity !== null) {
            $property->mapping['similarity'] = $annotation->similarity;
        }
        if ($annotation->store !== null) {
            $property->mapping['store'] = $annotation->store;
        }
        if ($annotation->termVector !== null) {
            $property->mapping['term_vector'] = $annotation->termVector;
        }
    }
}
