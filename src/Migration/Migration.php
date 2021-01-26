<?php

declare(strict_types=1);

namespace Zp\Supple\Migration;

use Zp\Supple\ClientInterface;
use Zp\Supple\SuppleException;

class Migration
{
    /** @var string */
    private $name;

    /** @var array<MigrationCommandInterface> */
    private $commands = [];

    /** @var array<MigrationCommandDetails> */
    private $details = [];

    /** @var bool */
    private $isExecuted = false;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<MigrationCommandDetails>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    public function addCommand(MigrationCommandInterface $migration, MigrationCommandDetails $details): void
    {
        $this->commands[] = $migration;
        $this->details[] = $details;
    }

    public function canExecute(): bool
    {
        return $this->isExecuted === false && count($this->commands) > 0;
    }

    /**
     * @param ClientInterface $client
     * @throws SuppleException
     */
    public function execute(ClientInterface $client): void
    {
        if (!$this->canExecute()) {
            throw new SuppleException('unable to execute migrations');
        }
        foreach ($this->commands as $index => $command) {
            try {
                $command->execute($client);
            } catch (\Throwable $e) {
                $this->details[$index]->setException($e);
            }
        }
        $this->isExecuted = true;
    }
}
