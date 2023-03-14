<?php

namespace Os\Framework\DataAbstractionLayer\Exception;

use JetBrains\PhpStorm\Pure;
use Os\Framework\Exception\FrameworkException;
use Throwable;

class DatabaseStatementExecutionFailed extends FrameworkException
{
    #[Pure]
    public function __construct(protected string $sql, ?Throwable $previous = null)
    {
        parent::__construct(sprintf("Execution of sql statement '%s' failed", $sql), $previous);
    }

    /**
     * @return string
     */
    public function getSql(): string
    {
        return $this->sql;
    }
}