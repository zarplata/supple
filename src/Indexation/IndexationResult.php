<?php

declare(strict_types=1);

namespace Zp\Supple\Indexation;

class IndexationResult
{
    /** @var array<ChangeSet> */
    private $changeSets;

    /**
     * @param array<ChangeSet> $changeSets
     */
    public function __construct(array $changeSets)
    {
        $this->changeSets = $changeSets;
    }

    public function hasErrors(): bool
    {
        foreach ($this->changeSets as $changeSet) {
            if ($changeSet->hasErrors()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return \Generator<IndexationError>
     */
    public function getErrors(): \Generator
    {
        foreach ($this->changeSets as $changeSet) {
            if ($changeSet->hasErrors()) {
                yield from $changeSet->getErrors();
            }
        }
    }

    /**
     * @param ?object $document
     * @param ?string $id
     * @return iterable<IndexationError>
     */
    public function getErrorsForDocumentOrID(?object $document, ?string $id): iterable
    {
        $errors = [];
        foreach ($this->changeSets as $changeSet) {
            if ($document !== null && $changeSet->getDocument() === $document) {
                $errors[] = $changeSet->getErrors();
            }
            if ($id !== null && $changeSet->getID() === $id) {
                $errors[] = $changeSet->getErrors();
            }
        }
        return array_merge(...$errors);
    }
}
