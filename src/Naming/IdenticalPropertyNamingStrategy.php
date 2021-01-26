<?php

declare(strict_types=1);

namespace Zp\Supple\Naming;

use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use Zp\Supple\NamingStrategyInterface;

final class IdenticalPropertyNamingStrategy implements NamingStrategyInterface
{
    public function translate(string $name): string
    {
        return $name;
    }

    public function getSerializerNameStrategy(): SerializedNameAnnotationStrategy
    {
        return new SerializedNameAnnotationStrategy(
            new \JMS\Serializer\Naming\IdenticalPropertyNamingStrategy()
        );
    }
}
