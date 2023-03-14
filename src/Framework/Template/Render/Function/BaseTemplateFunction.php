<?php

namespace Os\Framework\Template\Render\Function;

use Os\Framework\DependencyInjection\ContainerInterface;

class BaseTemplateFunction implements TemplateFunctionInterface
{
    public function __construct(protected ContainerInterface $container){}

    public function getFunctions(): array
    {
        return [
            [
                "name" => "json",
                "callable" => "json"
            ],
            [
                "name" => "javascript_entry",
                "callable" => "javascriptEntry"
            ]
        ];
    }

    public function json(string|array $data){
        if(is_array($data))
            return \json_encode($data);
        return \json_decode($data);
    }

    public function javascriptEntry(string $entry){
        return "TODO: Add javascript entry: ".$entry;
    }
}