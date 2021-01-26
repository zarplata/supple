<?php

declare(strict_types=1);

namespace Zp\Supple\Indexation;

use Zp\Supple\Elasticsearch\Index;

class ChangeSet implements RoutingInterface
{
    /** @var ?string */
    private $id;

    /** @var ?string */
    private $source;

    /** @var ?object */
    private $document;

    /** @var array<Index> */
    private $indexTo = [];

    /** @var array<Index> */
    private $deleteFrom = [];

    /** @var array<IndexationError> */
    private $errors = [];

    /**
     * @param ?string $id
     * @param object|null $document
     * @param ?string $source
     */
    public function __construct(?string $id, ?object $document, ?string $source)
    {
        $this->id = $id;
        $this->document = $document;
        $this->source = $source;
    }

    /**
     * @return ?string
     */
    public function getID(): ?string
    {
        return $this->id;
    }

    /**
     * @return ?object
     */
    public function getDocument(): ?object
    {
        return $this->document;
    }

    /**
     * @return ?string
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    public function addError(IndexationError $error): void
    {
        $this->errors[] = $error;
    }

    /**
     * @return array<IndexationError>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * @return array<Index>
     */
    public function getIndexTo(): array
    {
        return $this->indexTo;
    }

    /**
     * @return array<Index>
     */
    public function getDeleteFrom(): array
    {
        return $this->deleteFrom;
    }

    /**
     * @param Index $index
     * @throws IndexationException
     */
    public function indexTo(Index $index): void
    {
        if ($this->source === null) {
            throw new IndexationException('unable to index an empty document');
        }
        $this->indexTo[] = $index;
    }

    /**
     * @param Index $index
     * @throws IndexationException
     */
    public function deleteFrom(Index $index): void
    {
        if ($this->id === null) {
            throw new IndexationException('unable to delete the document without an identifier');
        }
        $this->deleteFrom[] = $index;
    }

    public function hasChanges(): bool
    {
        return count($this->indexTo) > 0 || count($this->deleteFrom) > 0;
    }
}
