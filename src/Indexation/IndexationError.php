<?php

declare(strict_types=1);

namespace Zp\Supple\Indexation;

final class IndexationError
{
    /** @var string */
    private $action;

    /** @var string */
    private $index;

    /** @var string */
    private $id;

    /** @var string */
    private $type;

    /** @var string */
    private $reason;

    /** @var ?self */
    private $causedBy;

    /**
     * @param string $action
     * @param string $index
     * @param string $id
     * @param string $type
     * @param string $reason
     * @param ?self $causedBy
     */
    public function __construct(
        string $action,
        string $index,
        string $id,
        string $type,
        string $reason,
        ?IndexationError $causedBy
    ) {
        $this->action = $action;
        $this->index = $index;
        $this->id = $id;
        $this->type = $type;
        $this->reason = $reason;
        $this->causedBy = $causedBy;
    }

    /**
     * @param string $action
     * @param string $index
     * @param string $id
     * @param array<string, mixed> $error
     * @return self
     */
    public static function create(string $action, string $index, string $id, array $error): self
    {
        $causedBy = isset($error['caused_by'])
            ? self::create($action, $index, $id, $error['caused_by'])
            : null;

        return new self($action, $index, $id, $error['type'], $error['reason'], $causedBy);
    }

    public function __toString(): string
    {
        $message = sprintf(
            '[index: %s; id: %s] %s document: %s: %s',
            $this->index,
            $this->id,
            $this->action,
            $this->type,
            $this->reason
        );
        if ($this->causedBy) {
            return sprintf(
                '%s; caused by %s',
                $message,
                (string)$this->causedBy
            );
        }
        return $message;
    }
}
