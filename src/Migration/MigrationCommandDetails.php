<?php

declare(strict_types=1);

namespace Zp\Supple\Migration;

use Throwable;

class MigrationCommandDetails
{
    /** @var string */
    private $name;

    /** @var string */
    private $local;

    /** @var string */
    private $remote;

    /** @var Throwable */
    private $exception;

    public function __construct(string $name, string $local, string $remote)
    {
        $this->name = $name;
        $this->local = $local;
        $this->remote = $remote;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLocal(): string
    {
        return $this->local;
    }

    public function getRemote(): string
    {
        return $this->remote;
    }

    public function hasError(): bool
    {
        return $this->exception !== null;
    }

    public function getException(): Throwable
    {
        return $this->exception;
    }

    public function setException(Throwable $exception): void
    {
        $this->exception = $exception;
    }
}
