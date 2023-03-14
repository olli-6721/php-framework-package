<?php

namespace Os\Framework\Http\Client;

class HttpOptions
{
    protected array $options;

    public function __construct(){
        $this->options = [];
    }

    public function getOptions(): array {
        return $this->options;
    }

    public function setBody(string|array $content): static
    {
        $this->options["body"] = $content;
        return $this;
    }

    public function setMultipleHeaders(array $headers): static
    {
        foreach($headers as $key => $value){
            if(!is_string($key)) continue;
            $this->setHeader($key, $value);
        }
        return $this;
    }

    public function setHeader(string $key, string $value): static
    {
        if(!isset($this->options["headers"])) $this->options["headers"] = [];
        $this->options["headers"][$key] = $value;
        return $this;
    }

    public function removeHeader(string $key): static
    {
        if(!isset($this->options["headers"])) return $this;
        unset($this->options["headers"][$key]);
        return $this;
    }

    public function addQuery(string $key, string $value): static
    {
        if(!isset($this->options["query"])) $this->options["query"] = [];
        $this->options["query"][$key] = $value;
        return $this;
    }
}