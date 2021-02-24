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
            $prefix = is_numeric($name) ? '' : sprintf('%s=', $name);
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
     * @param string $tarName
     * @param array<string, mixed> $properties
     * @return Tag\GenericTag
     */
    private function composeAnnotation(string $tarName, array $properties): Tag\GenericTag
    {
        return new Tag\GenericTag(sprintf('%s(%s)', $tarName, $this->composeAnnotationProperties($properties)));
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
            $classPropertyName = lcfirst(
                (string)preg_replace_callback(
                    '/_([a-z]+)/',
                    static function ($match) {
                        return ucfirst($match[1]);
                    },
                    $mappingPropertyName
                )
            );

            if (!isset($mappingProperty['type'])) {
                $mappingProperty['type'] = '';
            }

            $type = $mappingProperty['type'];

            switch ($type) {
                case 'object':
                    $objectClassName = sprintf('%s%s', $className, ucfirst($classPropertyName));
                    if ($namespace) {
                        $mappingProperty['targetClass'] = sprintf('\\%s\\%s', trim($namespace, '\\'), $objectClassName);
                    } else {
                        $mappingProperty['targetClass'] = sprintf('\\%s', $objectClassName);
                    }
                    $varTag = new Tag\VarTag(null, $objectClassName);
                    yield from $this->generateClass($namespace, $objectClassName, $mappingProperty['properties'], null);
                    break;

                case '':
                case 'nested':
                    $objectClassName = sprintf('%s%s', $className, ucfirst($classPropertyName));
                    if ($namespace) {
                        $mappingProperty['targetClass'] = sprintf('\\%s\\%s', trim($namespace, '\\'), $objectClassName);
                    } else {
                        $mappingProperty['targetClass'] = sprintf('\\%s', $objectClassName);
                    }
                    $varTag = new Tag\VarTag(null, sprintf('array<%s>', $objectClassName));
                    yield from $this->generateClass($namespace, $objectClassName, $mappingProperty['properties'], null);
                    break;

                default:
                    if (!array_key_exists($type, self::PHP_TYPES_MAP)) {
                        throw new UnexpectedValueException(sprintf('unexpected type: %s', $type));
                    }
                    $varTag = new Tag\VarTag(null, self::PHP_TYPES_MAP[$type]);
            }

            $property = (new PropertyGenerator($classPropertyName));
            $property
                ->omitDefaultValue()
                ->setDocBlock(
                    $this->createDocBlock([$this->composeAnnotation('Elastic\\Mapping', $mappingProperty), $varTag])
                );
            $classGenerator->addPropertyFromGenerator($property);
        }

        yield (new FileGenerator())
            ->setFilename($className . '.php')
            ->setNamespace($namespace)
            ->setUse('\\Zp\\Supple\\Annotation', 'Elastic')
            ->setClass($classGenerator);
    }
}
