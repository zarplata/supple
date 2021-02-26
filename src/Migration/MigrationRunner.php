<?php

declare(strict_types=1);

namespace Zp\Supple\Migration;

use Generator;
use Throwable;
use Zp\Supple\ClientInterface;
use Zp\Supple\Configuration\Configuration;
use Zp\Supple\Migration\Command\PutIndexMappingsMigrationCommand;
use Zp\Supple\Migration\Command\PutIndexMigrationCommand;
use Zp\Supple\Migration\Command\PutIndexSettingsCommand;
use Zp\Supple\Migration\Command\PutTemplateMigrationCommand;
use Zp\Supple\SuppleConfigurationException;
use Zp\Supple\SuppleException;

final class MigrationRunner
{
    /** @var ClientInterface */
    private $client;

    /** @var array<string, Configuration> */
    private $configurations;

    /** @var array<Migration> */
    private $migrations = [];

    /**
     * @param ClientInterface $client
     * @param array<string, Configuration> $configurations
     * @throws SuppleConfigurationException
     */
    public function __construct(ClientInterface $client, array $configurations)
    {
        $this->client = $client;
        $this->configurations = $configurations;
        $this->prepareAvailableMigrations();
    }

    public function hasMigrations(): bool
    {
        return count($this->migrations) > 0;
    }

    /**
     * @return Generator<Migration>
     * @throws SuppleException
     */
    public function execute(): Generator
    {
        foreach ($this->migrations as $plan) {
            $plan->execute($this->client);
            yield $plan;
        }
    }

    private function prepareAvailableMigrations(): void
    {
        foreach ($this->configurations as $name => $configuration) {
            $migration = new Migration($name);

            try {
                $this->prepareTemplateMigration($migration, $configuration);
            } catch (Throwable $e) {
                $message = sprintf('unable to prepare template migration for configuration %s', $name);
                throw new SuppleException($message, 0, $e);
            }

            try {
                $this->prepareIndexMigration($migration, $configuration);
            } catch (Throwable $e) {
                $message = sprintf('unable to prepare index migration for configuration %s', $name);
                throw new SuppleException($message, 0, $e);
            }

            if ($migration->canExecute()) {
                $this->migrations[] = $migration;
            }
        }
    }

    private function prepareTemplateMigration(Migration $migration, Configuration $configuration): void
    {
        $localTemplate = $configuration->getTemplate();
        if ($localTemplate === null) {
            return;
        }
        if ($this->client->hasTemplate($localTemplate->getName())) {
            $remoteTemplate = $this->client->getTemplate($localTemplate->getName());
            $localTemplate = $localTemplate
                ->copyTypeFrom($remoteTemplate)
                ->mergeMissingSettingsFrom($remoteTemplate);

            if (!$localTemplate->compareTo($remoteTemplate)) {
                $migration->addCommand(
                    new PutTemplateMigrationCommand($localTemplate),
                    new MigrationCommandDetails(
                        sprintf('template `%s` has been updated', $localTemplate->getName()),
                        (string)($localTemplate),
                        (string)$remoteTemplate
                    )
                );
            }
        } else {
            $migration->addCommand(
                new PutTemplateMigrationCommand($localTemplate),
                new MigrationCommandDetails(
                    sprintf('template `%s` has been created', $localTemplate->getName()),
                    (string)($localTemplate),
                    ''
                )
            );
        }
    }

    private function prepareIndexMigration(Migration $migration, Configuration $configuration): void
    {
        foreach ($configuration->getIndices() as $localIndex) {
            $hasIndex = $this->client->hasIndex($localIndex->getName());

            if (!$hasIndex) {
                $migration->addCommand(
                    new PutIndexMigrationCommand($localIndex),
                    new MigrationCommandDetails(
                        sprintf('index `%s` has been created', $localIndex->getName()),
                        (string)($localIndex),
                        ''
                    )
                );
                continue;
            }

            $remoteIndex = $this->client->getIndex($localIndex->getName())->removeNotUpdateableSettings();
            $localIndex = $localIndex
                ->removeNotUpdateableSettings()
                ->copyTypeFrom($remoteIndex)
                ->mergeMissingSettingsFrom($remoteIndex);

            if (!$localIndex->compareMappingsTo($remoteIndex)) {
                $migration->addCommand(
                    new PutIndexMappingsMigrationCommand($localIndex),
                    new MigrationCommandDetails(
                        sprintf('index `%s` mappings has been updated', $localIndex->getName()),
                        (string)($localIndex),
                        (string)$remoteIndex
                    )
                );
            }

            if (!$localIndex->compareSettingsTo($remoteIndex)) {
                $command = new PutIndexSettingsCommand($localIndex);

                if ($localIndex->compareNonDynamicSettingsTo($remoteIndex)) {
                    $migration->addCommand(
                        $command->wrapCloseIndex(),
                        new MigrationCommandDetails(
                            sprintf('index `%s` settings has been updated [index was closed]', $localIndex->getName()),
                            (string)($localIndex),
                            (string)$remoteIndex
                        )
                    );
                } else {
                    $migration->addCommand(
                        $command,
                        new MigrationCommandDetails(
                            sprintf('index `%s` settings has been updated', $localIndex->getName()),
                            (string)($localIndex),
                            (string)$remoteIndex
                        )
                    );
                }
            }
        }
    }
}
