<?php

declare(strict_types=1);

namespace Zp\Supple\Indexation;

use Zp\Supple\Elasticsearch\Index;

interface RoutingInterface
{
    /**
     * @param Index $index
     * @throws IndexationException
     */
    public function indexTo(Index $index): void;

    /**
     * @param Index $index
     * @throws IndexationException
     */
    public function deleteFrom(Index $index): void;
}
