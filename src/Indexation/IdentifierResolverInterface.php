<?php

declare(strict_types=1);

namespace Zp\Supple\Indexation;

interface IdentifierResolverInterface
{
    public function resolve(object $document): ?string;
}
