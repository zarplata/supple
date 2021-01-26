<?php

declare(strict_types=1);

namespace Zp\Supple\Indexation;

use Zp\Supple\Elasticsearch\Index;

class RouterFactory
{
    public static function createFanout(): RouterInterface
    {
        return new class implements RouterInterface {
            public function route(object $document, Index $index, \Zp\Supple\Indexation\RoutingInterface $routing): void
            {
                $routing->indexTo($index);
            }
        };
    }
}
