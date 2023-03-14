<?php

namespace Os\Framework\Http\Response;

class Response
{
    public function __construct(protected ?string $content = null, protected int $code = 200){}

    /**
     * @return ?string
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }
}