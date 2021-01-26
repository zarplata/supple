<?php

declare(strict_types=1);

namespace Zp\Supple;

use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;

interface NamingStrategyInterface
{
    public function translate(string $name): string;

    public function getSerializerNameStrategy(): SerializedNameAnnotationStrategy;
}
