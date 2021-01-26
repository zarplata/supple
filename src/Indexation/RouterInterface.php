<?php

declare(strict_types=1);

namespace Zp\Supple\Indexation;

use Zp\Supple\Elasticsearch\Index;

interface RouterInterface
{
    public function route(object $document, Index $index, RoutingInterface $routing): void;
}
