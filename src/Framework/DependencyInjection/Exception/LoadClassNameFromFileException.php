<?php

namespace Os\Framework\DependencyInjection\Exception;

use Throwable;

class LoadClassNameFromFileException extends \Os\Framework\Exception\FrameworkException
{
    public function __construct(protected string $fileName, protected ?string $className, protected ?string $namespace, ?Throwable $previous = null)
    {
        $baseMessage = sprintf("Error loading qualified class name from file '%s'", $this->fileName);
        parent::__construct(message: match(true){
            ($this->className !== null && $this->namespace === null) => sprintf("%s (className found '%s' but no namespace found)", $baseMessage, $this->className),
            ($this->className === null && $this->namespace !== null) => sprintf("%s (namespace found '%s' but no className found)", $baseMessage, $this->namespace),
            default => sprintf("%s (className and namespace not found)", $baseMessage)
        }, previous: $previous);
    }
}