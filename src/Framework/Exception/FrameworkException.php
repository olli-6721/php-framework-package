<?php

namespace Os\Framework\Exception;

use JetBrains\PhpStorm\Pure;
use ReturnTypeWillChange;
use Throwable;

class FrameworkException extends \Exception
{

    #[Pure]
    public function __construct(protected $message, protected $code = 400, protected ?Throwable $previous = null)
    {
        parent::__construct($this->message, $this->code, $this->previous);
    }

    public static function createFrom(Throwable $e): static
    {
        return new static($e->getMessage(), $e->getCode(), $e->getPrevious());
    }

    #[Pure]
    public function __toString(): string
    {
        $baseMessage = sprintf("[%d] '%s' thrown", $this->getCode(), $this->getMessage());
        return match(true){
            $this->getFile() !== "" && $this->getLine() > -1 => sprintf("%s on line %d in file %s", $baseMessage, $this->getLine(), $this->getFile()),
            $this->getFile() !== "" && !($this->getLine() > -1) => sprintf("%s in file %s", $baseMessage, $this->getFile()),
            $this->getFile() == "" && $this->getLine() > -1 => sprintf("%s on line %d in unknown file", $baseMessage, $this->getLine()),
            default => $baseMessage
        };
    }
}