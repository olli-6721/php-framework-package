<?php

namespace Os\Framework\Config\Exception;

use Throwable;

class EnvFileNotFoundException extends \Os\Framework\Exception\FrameworkException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(message: sprintf(".env not found in BASE_PATH (%s)", BASE_PATH), previous: $previous);
    }
}