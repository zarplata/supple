<?php

declare(strict_types=1);

namespace Zp\Supple\Tests\Metadata;

use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Zp\Supple\Annotation as Elastic;
use Zp\Supple\Elasticsearch\Index;
use Zp\Supple\Metadata\MetadataFactory;
use Zp\Supple\Metadata\MetadataProperty;
use Zp\Supple\Naming\CamelCaseNamingStrategy;
use Zp\Supple\SuppleConfigurationException;

class MetadataFactoryTest extends TestCase
{
    public function testClassName(): void
    {
        // arrange
        $document = $this->createDocument();
        $metadataFactory = new MetadataFactory(new AnnotationReader(), new CamelCaseNamingStrategy());
        // act
        $metadata = $metadataFactory->create(get_class($document));
        // assert
        self::assertStringStartsWith('class@anonymous', $metadata->className);
    }

    public function testIndices(): void
    {
        // arrange
        $document = $this->createDocument();
        $metadataFactory = new MetadataFactory(new AnnotationReader(), new CamelCaseNamingStrategy());
        // act
        $metadata = $metadataFactory->create(get_class($document));
        // assert
        self::assertCount(2, $metadata->indices);
        self::assertEquals(['index1', 'index2'], $metadata->indices);
    }

    public function testIdentifier(): void
    {
        // arrange
        $document = $this->createDocument();
        $metadataFactory = new MetadataFactory(new AnnotationReader(), new CamelCaseNamingStrategy());
        // act
        $metadata = $metadataFactory->create(get_class($document));
        // assert
        self::assertInstanceOf(MetadataProperty::class, $metadata->identifierProperty);
        self::assertTrue($metadata->identifierProperty->isIdentifier ?? false);
        self::assertEquals('id', $metadata->identifierProperty->name ?? '');
    }

    public function testProperties(): void
    {
        // arrange
        $document = $this->createDocument();
        $metadataFactory = new MetadataFactory(new AnnotationReader(), new CamelCaseNamingStrategy());
        // act
        $metadata = $metadataFactory->create(get_class($document));
        // assert
        self::assertCount(2, $metadata->properties);
        self::assertContainsOnlyInstancesOf(MetadataProperty::class, $metadata->properties);
        self::assertArrayHasKey('id', $metadata->properties);
        self::assertArrayHasKey('name', $metadata->properties);
    }

    public function testIdentifierWithoutMappingShouldBeKeyword(): void
    {
        // arrange
        $document = $this->createDocument();
        $metadataFactory = new MetadataFactory(new AnnotationReader(), new CamelCaseNamingStrategy());
        // act
        $metadata = $metadataFactory->create(get_class($document));
        // assert
        self::assertArrayHasKey('id', $metadata->properties);
        self::assertEquals(
            ["type" => "keyword"],
            $metadata->properties['id']->mapping
        );
    }

    public function testPropertyMapping(): void
    {
        // arrange
        $document = $this->createDocument();
        $metadataFactory = new MetadataFactory(new AnnotationReader(), new CamelCaseNamingStrategy());
        // act
        $metadata = $metadataFactory->create(get_class($document));
        // assert
        self::assertArrayHasKey('name', $metadata->properties);
        self::assertEquals(
            ["type" => "text"],
            $metadata->properties['name']->mapping
        );
    }

    private function createDocument(): object
    {
        /**
         * @Elastic\Index(name="index1"),
         * @Elastic\Index(name="index2")
         */
        return new class () {
            /**
             * @var string
             * @Elastic\ID
             * @Elastic\Mapping(type="keyword")
             */
            public $id;

            /**
             * @var string
             * @Elastic\Mapping(type="text")
             */
            public $name;
        };
    }
}
