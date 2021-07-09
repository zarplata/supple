<?php

declare(strict_types=1);

namespace Zp\Supple\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zp\Supple\Generator\Writer;
use Zp\Supple\Supple;

class CodeGenerateCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('supple:code:generate')
            ->addArgument('index', InputArgument::REQUIRED)
            ->addArgument('classname', InputArgument::REQUIRED)
            ->addArgument('directory', InputArgument::REQUIRED)
            ->addOption('namespace', null, InputOption::VALUE_OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Supple $supple */
        $supple = $this->getHelper('supple')->getSupple();
        /** @var string $index */
        $index = $input->getArgument('index');
        /** @var class-string $className */
        $className = $input->getArgument('classname');
        /** @var string $directory */
        $directory = $input->getArgument('directory');
        /** @var ?string $namespace */
        $namespace = $input->getOption('namespace');

        $writers = $supple->generateCode()->generate($index, $className, (string)$namespace);

        /** @var Writer $writer */
        foreach ($writers as $writer) {
            $output->writeln(
                sprintf(
                    'write class <info>%s</info> to <info>%s</info>',
                    $writer->getClassName(),
                    $writer->getFullPath($directory)
                )
            );
            $writer->write($directory);
        }
        return 0;
    }
}
