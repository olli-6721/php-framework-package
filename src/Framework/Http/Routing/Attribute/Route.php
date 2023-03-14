<?php

namespace Os\Framework\Http\Routing\Attribute;

use Attribute;
use Os\Framework\Debug\Dumper;
use Os\Framework\Http\Request\Request;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route
{
    public const VARIABLE_PATTERN = "(.[^\/?]*)";
    public const VARIABLE_IDENTIFY_PATTERN = "(\{.[^\/?]*\})";

    protected string $regexPattern;
    protected ?array $parameters;

    public function __construct(protected string $urlPattern, protected string $name, protected ?array $methods = null, protected bool $ignoreQuery = false)
    {
        $this->parameters = null;
        $this->generateRegexPattern();
        if($this->methods !== null){
            $this->methods = array_map(function($method){
                return strtolower($method);
            }, $this->methods);
        }
    }

    public function resolve(Request $request): bool
    {
        if($this->methods !== null && !in_array(strtolower($request->getMethod()), $this->methods))
            return false;
        $uri = $this->ignoreQuery ? $request->getPath() : $request->getUri();
        if($this->ignoreQuery)
            $variableIdentifyPattern = str_replace("?", "", self::VARIABLE_IDENTIFY_PATTERN);
        else
            $variableIdentifyPattern = self::VARIABLE_IDENTIFY_PATTERN;
        $matches = [];
        $pregMatch = preg_match_all($this->regexPattern, $uri, $matches);
        if(is_int($pregMatch) && !empty($matches)){
            $parameterPattern = sprintf("/%s/", preg_replace($variableIdentifyPattern, $variableIdentifyPattern, str_replace("/", "\/", $this->urlPattern)));
            $parameterMatches = [];
            preg_match_all($parameterPattern, $this->urlPattern, $parameterMatches);
            if(reset($matches[0]) !== $uri) return false;
            unset($matches[0]);
            unset($parameterMatches[0]);
            foreach($matches as $key => $match){
                $keyValue = $parameterMatches[$key] ?? null;
                if($keyValue === null) continue;
                $keyValue = str_replace(["{", "}", " "], "", reset($keyValue));
                if(!isset($this->parameters)) $this->parameters = [];
                $this->parameters[$keyValue] = reset($match);
            }
        }
        return !($pregMatch === false) && $pregMatch > 0;
    }

    public function getParameters(): array
    {
        return $this->parameters ?? [];
    }

    protected function generateRegexPattern(){
        if($this->ignoreQuery){
            $variableIdentifyPattern = str_replace("?", "", self::VARIABLE_IDENTIFY_PATTERN);
            $variablePattern = str_replace("?", "", self::VARIABLE_PATTERN);
        }
        else {
            $variableIdentifyPattern = self::VARIABLE_IDENTIFY_PATTERN;
            $variablePattern = self::VARIABLE_PATTERN;
        }

        $this->regexPattern = sprintf("/%s/", preg_replace($variableIdentifyPattern, $variablePattern, str_replace("/", "\/", $this->urlPattern)));
    }
}