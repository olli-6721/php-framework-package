<?php

namespace Os\Framework\Filesystem\FileDefinition\Php;

trait PhpAttributeTrait
{
    protected array $attributes = [];

    public function addAttribute(string $className, array $arguments): static
    {
        $attrStr = sprintf("%s(", $className);

        $lastIndex = array_key_last($arguments);
        foreach($arguments as $key => $argument){

            if($lastIndex === $key)
                $attrStr = sprintf("%s%s", $attrStr, $argument);
            else
                $attrStr = sprintf("%s%s, ", $attrStr, $argument);
        }
        $attrStr = sprintf("%s)", $attrStr);
        $this->attributes[] = $attrStr;
        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}