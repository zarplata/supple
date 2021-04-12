<?php

declare(strict_types=1);

namespace Zp\Supple\Console\Command;

use Jfcherng\Diff\Differ;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zp\Supple\Migration\MigrationDiffRenderer;
use Zp\Supple\Supple;

use function explode;

class MigrationExecuteCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('supple:migration:execute')
            ->setDescription('Generate and execute migrations')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only print migrations')
            ->addOption('no-diff', null, InputOption::VALUE_NONE, 'Does not print migration diff')
            ->addOption('full-diff', null, InputOption::VALUE_NONE, 'Print full migration diff');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Supple $supple */
        $supple = $this->getHelper('supple')->getSupple();
        $runner = $supple->migrate((bool)$input->getOption('dry-run'));

        if (!$runner->hasMigrations()) {
            $output->writeln('<comment>no migrations found for execute</comment>');
            return 0;
        }

        $context = $input->getOption('full-diff') ? Differ::CONTEXT_ALL : 5;
        $differ = Differ::getInstance()->setOptions(['context' => $context, 'ignoreWhitespace' => true]);

        $renderer = new MigrationDiffRenderer();
        $showDiff = (bool)$input->getOption('no-diff') === false;
        $hasErrors = false;

        foreach ($runner->executeGenerator() as $migration) {
            $output->writeln(sprintf('<info>execute migration for document `%s`</info>', $migration->getName()));
            if ($showDiff) {
                $output->writeln('');
            }

            foreach ($migration->getDetails() as $details) {
                if ($showDiff) {
                    $output->writeln(sprintf(" <comment>%s</comment>", $details->getName()));

                    $diff = $differ->setOldNew(
                        explode("\n", $details->getRemote()),
                        explode("\n", $details->getLocal())
                    );

                    $output->writeln($renderer->render($diff));
                } else {
                    $output->writeln(sprintf("<comment>%s</comment>", $details->getName()));
                }

                if ($details->hasError()) {
                    $output->writeln(sprintf('<error>%s</error>', (string)$details->getException()));
                    $hasErrors = true;
                }
            }
        }

        return (int)$hasErrors;
    }
}
