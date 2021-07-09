<?php

namespace Zp\Supple\Generator;

use Laminas\Code\Generator\FileGenerator;

class Writer
{
    /** @var FileGenerator */
    private $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        $this->fileGenerator = $fileGenerator;
    }

    public function getClassName(): string
    {
        return $this->fileGenerator->getClass()->getName();
    }

    public function getFullPath(string $directory): string
    {
        return sprintf('%s/%s', $directory, $this->fileGenerator->getFilename());
    }

    public function write(string $directory): void
    {
        file_put_contents(
            $this->getFullPath($directory),
            $this->fileGenerator->generate()
        );
    }
}
