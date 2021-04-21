<?php

declare(strict_types=1);

namespace Zp\Supple\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Zp\Supple\Console\Command\CodeGenerateCommand;
use Zp\Supple\Console\Command\MigrationExecuteCommand;
use Zp\Supple\Supple;
use Zp\Supple\Version;

class ConsoleRunner
{
    /**
     * Create a Symfony Console HelperSet
     *
     * @param Supple $supple
     * @return HelperSet
     */
    public static function createHelperSet(Supple $supple): HelperSet
    {
        return new HelperSet(['supple' => new ConsoleHelper($supple)]);
    }

    /**
     * Runs console with the given helper set.
     *
     * @param HelperSet $helperSet
     * @param array<Command> $commands
     * @return void
     */
    public static function run(HelperSet $helperSet, array $commands = []): void
    {
        $cli = self::createApplication($helperSet, $commands);
        $cli->run();
    }

    /**
     * Creates a console application with the given helperset and
     * optional commands.
     *
     * @param HelperSet $helperSet
     * @param array<Command> $commands
     * @return Application
     */
    public static function createApplication(HelperSet $helperSet, array $commands = []): Application
    {
        $cli = new Application('Supple CLI', Version::VERSION);
        $cli->setCatchExceptions(true);
        $cli->setHelperSet($helperSet);
        self::addCommands($cli);
        $cli->addCommands($commands);

        return $cli;
    }

    public static function addCommands(Application $cli): void
    {
        $cli->addCommands(
            [
                new MigrationExecuteCommand(),
                new CodeGenerateCommand(),
            ]
        );
    }
}
