<?php

declare(strict_types=1);

namespace Zp\Supple\Indexation;

class IdentifierResolverFactory
{
    public static function createEmptyResolver(): IdentifierResolverInterface
    {
        return new class implements IdentifierResolverInterface {
            public function resolve(object $document): ?string
            {
                return null;
            }
        };
    }

    public static function createPropertyResolver(string $propertyName): IdentifierResolverInterface
    {
        return new class ($propertyName) implements IdentifierResolverInterface {
            /** @var string */
            private $propertyName;

            public function __construct(string $propertyName)
            {
                $this->propertyName = $propertyName;
            }

            public function resolve(object $document): ?string
            {
                return (string)$document->{$this->propertyName};
            }
        };
    }
}
