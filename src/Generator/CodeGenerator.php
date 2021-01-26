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
use Sabre\DAV\Xml\Element\Prop;
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

        foreach ($mappingProperties as $mappingName => $mappingProperty) {
            $normalizedPropertyName = $this->toCamelCase((string)$mappingName);

            if (is_numeric($mappingName)) {
                $property = new PropertyGenerator(sprintf('_%s', $normalizedPropertyName));
                $mappingProperty['name'] = (string)$mappingName;
            } else {
                $property = new PropertyGenerator($normalizedPropertyName);
                if ($mappingName !== $normalizedPropertyName) {
                    $mappingProperty['name'] = (string)$mappingName;
                }
            }

            if (!isset($mappingProperty['type'])) {
                $mappingProperty['type'] = '';
            }

            $annotations = [];
            switch ($mappingProperty['type']) {
                case '':
                case 'object':
                    $objectClassName = sprintf('%s%s', $className, ucfirst($normalizedPropertyName));
                    $mappingProperty['targetClass'] = $this->composeTargetClass($namespace, $objectClassName);
                    $annotations[] = new Tag\VarTag(null, $objectClassName);
                    $annotations[] = $this->composeAnnotation('Elastic\\EmbeddedProperty', $mappingProperty);
                    yield from $this->generateClass($namespace, $objectClassName, $mappingProperty['properties'], null);
                    break;

                case 'nested':
                    $objectClassName = sprintf('%s%s', $className, ucfirst($normalizedPropertyName));
                    $mappingProperty['targetClass'] = $this->composeTargetClass($namespace, $objectClassName);
                    $annotations[] = new Tag\VarTag(null, sprintf('array<%s>', $objectClassName));
                    $annotations[] = $this->composeAnnotation('Elastic\\EmbeddedProperty', $mappingProperty);
                    yield from $this->generateClass($namespace, $objectClassName, $mappingProperty['properties'], null);
                    break;

                default:
                    if (!array_key_exists($mappingProperty['type'], self::PHP_TYPES_MAP)) {
                        throw new UnexpectedValueException(sprintf('unexpected type: %s', $mappingProperty['type']));
                    }
                    $annotations[] = new Tag\VarTag(null, self::PHP_TYPES_MAP[$mappingProperty['type']]);
                    $annotations[] = $this->composeAnnotation('Elastic\\Property', $mappingProperty);
            }

            $property->omitDefaultValue();
            $property->setDocBlock($this->createDocBlock($annotations));
            $classGenerator->addPropertyFromGenerator($property);
        }

        yield (new FileGenerator())
            ->setFilename($className . '.php')
            ->setNamespace($namespace)
            ->setUse('Zp\\Supple\\Annotation', 'Elastic')
            ->setClass($classGenerator);
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
     * @param array<mixed> $properties
     * @return string
     */
    private function composeAnnotationProperties(array $properties, bool $root = true): string
    {
        $generated = [];
        foreach ($properties as $name => $value) {
            if ($root === true && $name === 'properties') {
                continue;
            }

            if (is_numeric($name)) {
                $assign = '';
            } else {
                $quotize = $root
                    ? function (string $name): string {
                        return $name;
                    }
                    : function (string $name): string {
                        return sprintf('"%s"', $name);
                    };

                $stringifyName = (string)$name;
                $normalized = $root
                    ? $this->toCamelCase($stringifyName)
                    : $stringifyName;
                $assign = sprintf('%s=', $quotize($normalized));
            }

            if (is_array($value)) {
                $generated[] = sprintf('%s{%s}', $assign, $this->composeAnnotationProperties($value, false));
            } elseif (is_string($value)) {
                $generated[] = sprintf('%s"%s"', $assign, $value);
            } else {
                $generated[] = sprintf('%s%s', $assign, var_export($value, true));
            }
        }
        return sprintf('%s', implode(', ', $generated));
    }

    /**
     * @param array<Tag\TagInterface> $annotations
     * @return DocBlockGenerator
     */
    private function createDocBlock(array $annotations): DocBlockGenerator
    {
        return DocBlockGenerator::fromArray(['tags' => $annotations])->setWordWrap(false);
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
