<?php

namespace Os\Framework\Template\Render\Function;

use Os\Framework\Debug\Dumper;

class TemplateFunctionResolver
{
    protected array $functions;
    protected array $executors;

    /** @param TemplateFunctionInterface[] $templateFunctionLibs */
    public function __construct(array $templateFunctionLibs){
        $this->functions = [];
        $this->executors = [];
        foreach($templateFunctionLibs as $functionLib){
            foreach($functionLib->getFunctions() as $function){
                if(empty($function["name"]) || empty($function["callable"])) continue;
                if(isset($this->functions[$function["name"]])) continue;
                $this->functions[$function["name"]] = ["callable" => $function["callable"], "executor" => $functionLib::class];
                if(isset($this->executors[$functionLib::class])) continue;
                $this->executors[$functionLib::class] = $functionLib;
            }
        }
    }

    public function resolve(string $functionName, ?string &$callable): ?TemplateFunctionInterface
    {
        if(empty($this->functions[$functionName])) return null;
        $functionData = $this->functions[$functionName];
        if(empty($this->executors[$functionData["executor"]])) return null;
        $callable = $functionData["callable"];
        return $this->executors[$functionData["executor"]];
    }
}