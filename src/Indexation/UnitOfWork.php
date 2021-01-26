<?php

declare(strict_types=1);

namespace Zp\Supple\Indexation;

use Zp\Supple\ClientInterface;

class UnitOfWork
{
    /** @var ClientInterface */
    private $client;

    /** @var array<ChangeSet> */
    private $changeSets = [];

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Add change set to current scope.
     *
     * @param ChangeSet $changeSet
     */
    public function add(ChangeSet $changeSet): void
    {
        if ($changeSet->hasChanges()) {
            $this->changeSets[] = $changeSet;
        }
    }

    /**
     * Commit current scope.
     *
     * @return array<ChangeSet>
     */
    public function commit(): array
    {
        if (count($this->changeSets) === 0) {
            return [];
        }
        try {
            $this->client->batch($this->changeSets);
            return $this->changeSets;
        } finally {
            $this->changeSets = [];
        }
    }

    /**
     * Clear current scope.
     */
    public function clear(): void
    {
        $this->changeSets = [];
    }
}
