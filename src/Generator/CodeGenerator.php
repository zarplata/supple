<?php

declare(strict_types=1);

namespace Zp\Supple\Generator;

use DateTime;
use Exception;
use Generator;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\DocBlock\Tag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\FileGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use RuntimeException;
use UnexpectedValueException;
use Zp\Supple\ClientInterface;
use Zp\Supple\Model\GeoPoint;

class CodeGenerator
{
    private const PHP_TYPES_MAP = [
        'long' => 'float',
        'float' => 'float',
        'double' => 'float',
        'keyword' => 'string',
        'text' => 'string',
        'boolean' => 'bool',
        'binary' => 'string',
        'geo_point' => '\\' . GeoPoint::class,
        'date' => '\\' . DateTime::class,
    ];

    /** @var ClientInterface */
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $indexName
     * @param string $className
     * @param string $namespace
     * @return Generator<FileGenerator>
     * @throws Exception
     */
    public function execute(string $indexName, string $className, string $namespace = ''): Generator
    {
        if (!$this->client->hasIndex($indexName)) {
            throw new RuntimeException(sprintf('index `%s` not found', $indexName));
        }

        $index = $this->client->getIndex($indexName);

        $annotations = [$this->composeAnnotation('Elastic\\Index', ['name' => $indexName])];
        foreach ($index->getSettings() as $name => $value) {
            $annotations[] = $this->composeAnnotation('Elastic\\IndexSetting', ['name' => $name, 'value' => $value]);
        }

        yield from $this->generateClass(
            ltrim($namespace, '\\'),
            $className,
            $index->getMappings()->getProperties(),
            $this->createDocBlock($annotations)
        );
    }

    /**
     * @param array<mixed> $mapping
     * @return string
     */
    private function composeAnnotationProperties(array $mapping): string
    {
        $options = [];
        foreach ($mapping as $name => $value) {
            if ($name === 'properties') {
                continue;
            }
            $prefix = is_numeric($name) ? '' : sprintf('%s=', $this->toCamelCase((string)$name));
            if (is_array($value)) {
                $options[] = sprintf('%s{%s}', $prefix, $this->composeAnnotationProperties($value));
            } elseif (is_string($value)) {
                $options[] = sprintf('%s"%s"', $prefix, $value);
            } else {
                $options[] = sprintf('%s%s', $prefix, var_export($value, true));
            }
        }
        return sprintf('%s', implode(', ', $options));
    }

    /**
     * @param string $name
     * @param array<string, mixed> $properties
     * @return Tag\GenericTag
     */
    private function composeAnnotation(string $name, array $properties): Tag\GenericTag
    {
        return new Tag\GenericTag(sprintf('%s(%s)', $name, $this->composeAnnotationProperties($properties)));
    }

    /**
     * @param array<Tag\TagInterface> $tags
     * @return DocBlockGenerator
     */
    private function createDocBlock(array $tags): DocBlockGenerator
    {
        return DocBlockGenerator::fromArray(['tags' => $tags])->setWordWrap(false);
    }

    /**
     * @param string $namespace
     * @param string $className
     * @param array<string, mixed> $mappingProperties
     * @param ?DocBlockGenerator $classDocBlock
     * @return Generator<FileGenerator>
     * @throws Exception
     */
    private function generateClass(
        string $namespace,
        string $className,
        array $mappingProperties,
        ?DocBlockGenerator $classDocBlock
    ): Generator {
        $classGenerator = new ClassGenerator($className, $namespace);
        if ($classDocBlock) {
            $classGenerator->setDocblock($classDocBlock);
        }

        foreach ($mappingProperties as $mappingPropertyName => $mappingProperty) {
            $propertyName = $this->toCamelCase($mappingPropertyName);

            if (!isset($mappingProperty['type'])) {
                $mappingProperty['type'] = '';
            }

            switch ($mappingProperty['type']) {
                case 'object':
                    $objectClassName = sprintf('%s%s', $className, ucfirst($propertyName));
                    $mappingProperty['targetClass'] = $this->composeTargetClass($namespace, $objectClassName);
                    $typeAnnotation = new Tag\VarTag(null, $objectClassName);
                    $mappingAnnotation = $this->composeAnnotation('Elastic\\EmbeddedMapping', $mappingProperty);
                    yield from $this->generateClass($namespace, $objectClassName, $mappingProperty['properties'], null);
                    break;

                case '':
                case 'nested':
                    $objectClassName = sprintf('%s%s', $className, ucfirst($propertyName));
                    $mappingProperty['targetClass'] = $this->composeTargetClass($namespace, $objectClassName);
                    $typeAnnotation = new Tag\VarTag(null, sprintf('array<%s>', $objectClassName));
                    $mappingAnnotation = $this->composeAnnotation('Elastic\\EmbeddedMapping', $mappingProperty);
                    yield from $this->generateClass($namespace, $objectClassName, $mappingProperty['properties'], null);
                    break;

                default:
                    if (!array_key_exists($mappingProperty['type'], self::PHP_TYPES_MAP)) {
                        throw new UnexpectedValueException(sprintf('unexpected type: %s', $mappingProperty['type']));
                    }
                    $typeAnnotation = new Tag\VarTag(null, self::PHP_TYPES_MAP[$mappingProperty['type']]);
                    $mappingAnnotation = $this->composeAnnotation('Elastic\\Mapping', $mappingProperty);
            }

            $property = (new PropertyGenerator($propertyName));
            $property
                ->omitDefaultValue()
                ->setDocBlock($this->createDocBlock([$mappingAnnotation, $typeAnnotation]));
            $classGenerator->addPropertyFromGenerator($property);
        }

        yield (new FileGenerator())
            ->setFilename($className . '.php')
            ->setNamespace($namespace)
            ->setUse('Zp\\Supple\\Annotation', 'Elastic')
            ->setClass($classGenerator);
    }

    private function composeTargetClass(string $namespace, string $objectClassName): string
    {
        if ($namespace) {
            $targetClass = sprintf('\\%s\\%s', trim($namespace, '\\'), $objectClassName);
        } else {
            $targetClass = sprintf('\\%s', $objectClassName);
        }
        return $targetClass;
    }

    private function toCamelCase(string $name): string
    {
        $replace = static function ($match) {
            return ucfirst($match[1]);
        };
        return lcfirst(
            (string)preg_replace_callback('/_([a-z]+)/', $replace, $name)
        );
    }
}
