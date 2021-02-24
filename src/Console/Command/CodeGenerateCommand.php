<?php

declare(strict_types=1);

namespace Zp\Supple\Console\Command;

use Laminas\Code\Generator\FileGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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
        /** @var string $className */
        $className = $input->getArgument('classname');
        /** @var string $directory */
        $directory = $input->getArgument('directory');
        /** @var ?string $namespace */
        $namespace = $input->getOption('namespace');

        $fileGenerators = $supple->generateCode()->execute(
            $index,
            $className,
            (string)$namespace
        );

        /** @var FileGenerator $fileGenerator */
        foreach ($fileGenerators as $fileGenerator) {
            $path = sprintf('%s/%s', $directory, $fileGenerator->getFilename());
            $output->writeln(sprintf('writing %s to %s', $fileGenerator->getClass()->getName(), $path));
            file_put_contents(
                $path,
                $fileGenerator->generate()
            );
        }
        return 0;
    }
}
