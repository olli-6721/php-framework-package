<?php

namespace Os\Framework\Template\Render;

use Os\Framework\DataAbstractionLayer\Service\DataType;
use Os\Framework\DataAbstractionLayer\Service\Uuid;
use Os\Framework\DependencyInjection\ContainerInterface;
use Os\Framework\Exception\FrameworkException;
use Os\Framework\Filesystem\Cache\FileBasedCacheProvider;
use Os\Framework\Filesystem\Filesystem;
use Os\Framework\Template\Render\Function\TemplateFunctionResolver;
use Os\Framework\Template\Render\Structure\TemplateStructureResolver;
use Os\Framework\Template\Render\Variable\TemplateVariable;

class TemplateRenderer
{
    public const MATCH_ACTION_CONTENT = "\{\{(.*)\}\}";
    public const MATCH_FUNCTION = "(.*)\((.*)\)";
    public const MATCH_STRUCTURE = "{%(.*)%}";

    protected string $templateName;
    protected string $templatePath;
    protected ?string $templateCacheKey;
    protected array $parameters;
    protected Filesystem $filesystem;
    protected TemplateFunctionResolver $templateFunctionResolver;
    protected TemplateStructureResolver $templateStructureResolver;

    public function __construct(protected ContainerInterface $container){
        $this->templateName = "";
        $this->templatePath = "";
        $this->templateCacheKey = null;
        $this->parameters = [];
        $this->filesystem = new Filesystem();
        $this->templateFunctionResolver = new TemplateFunctionResolver($this->container->getByGroup("template_function") ?? []);
        $this->templateStructureResolver = new TemplateStructureResolver();
    }

    /**
     * @throws \Exception
     */
    public function render(string $templateName, array $parameters = [], bool $pathIsAbsolute = false): string
    {
        $this->templateName = $templateName;
        $this->parameters = $parameters;
        return $this->resolveTemplate($this->getTemplateContent($templateName, $pathIsAbsolute));
    }

    /**
     * @throws FrameworkException
     */
    public function getTemplateContent(string $templateName, bool $pathIsAbsolute = false): string
    {
        if(!$pathIsAbsolute){
            $this->templatePath = sprintf("%s/templates/%s", BASE_PATH, $templateName);
            if(str_starts_with($templateName, "/"))
                $this->templatePath = sprintf("%s/templates%s", BASE_PATH, $templateName);
        }
        else{
            $this->templatePath = $templateName;
        }
        if(!$this->filesystem->fileExists($this->templatePath))
            throw new FrameworkException(sprintf("Template '%s' not found at '%s'", $templateName, $this->templatePath));
        $this->templateCacheKey = sprintf("template_%s", Uuid::v4($this->templateName));
        return file_get_contents($this->templatePath);
    }

    /**
     * @throws \Exception
     */
    protected function resolveTemplate(string $templateContent): string
    {
        if(ENV === "DEV" || !FileBasedCacheProvider::exists($this->templateCacheKey)){
            $this->resolveVariablesAndFunctions($templateContent);
            $this->resolveStructureElements($templateContent);
            FileBasedCacheProvider::set($this->templateCacheKey, $templateContent);
            return $templateContent;
        }
        return FileBasedCacheProvider::get($this->templateCacheKey);
    }

    /**
     * @throws FrameworkException
     */
    protected function resolveStructureElements(string &$templateContent): void
    {
        $template = $this->templateStructureResolver->resolve($templateContent, $this->parameters, clone $this);
        $template->parse();
        $templateContent = $template->getParsedContent();
    }

    /**
     * @throws FrameworkException
     */
    protected function resolveVariablesAndFunctions(string &$templateContent){
        $matches = [];
        preg_match_all(sprintf("/%s/", self::MATCH_ACTION_CONTENT), $templateContent, $matches);
        unset($matches[0]);
        $matches = reset($matches);
        foreach($matches as $match){
            $match = trim($match);
            $function = $this->getFunction($match);
            if($function === false){
                if(!isset($this->parameters[$match]))
                    throw new FrameworkException(sprintf("Could not resolve parameter '%s' in template '%s'", $match, $this->templateName));
                $value = $this->parameters[$match];
            }
            else {
                $callable = null;
                $executor = $this->templateFunctionResolver->resolve($function["name"], $callable);
                if($executor === null) throw new FrameworkException(sprintf("No executor found for function '%s'", $function["name"]));
                $functionArguments = $function["arguments"];
                try {
                    $value = $executor->$callable(...$functionArguments);
                }
                catch (\Throwable $e){
                    throw new FrameworkException(sprintf("Cant call function '%s'", $function["name"]), 400, $e);
                }
            }
            if(is_array($value) || is_object($value))
                $value = json_encode($value);
            $templateContent = $this->resolveValueInTemplate($templateContent, $match, $value);
        }
    }

    protected function resolveValueInTemplate(string $templateContent, string $match, string $value): string
    {
        $match = str_replace("(", "\(", str_replace(")", "\)", $match));
        return preg_replace(sprintf("/\{\{(\s*)%s(\s*)\}\}/", $match), $value, $templateContent);
    }

    /**
     * @throws FrameworkException
     */
    protected function getFunction(string $match): false|array
    {
        $matches = [];
        $pregMatch = preg_match_all(sprintf("/%s/", self::MATCH_FUNCTION), $match, $matches);
        if($pregMatch === 0 || $pregMatch === false) return false;
        unset($matches[0]);
        $functionName = reset($matches[1]);
        unset($matches[1]);
        if(count($matches) > 0){
            $matches = reset($matches);
            $arguments = explode(",", reset($matches));
            foreach($arguments as $key => $argument){
                $_argument = self::resolveValueContent(trim($argument), $this->parameters);
                if($_argument === null)
                    continue;
                $arguments[$key] = $_argument->getValue();
            }
        }
        return ["name" => $functionName, "arguments" => $arguments ?? []];
    }

    /**
     * @throws FrameworkException
     */
    public static function resolveValueContent(string $value, array $parameters = []): ?TemplateVariable
    {
        $_name = null;
        $_type = null;
        $_value = null;
        DataType::resolveBasicTypeAndValue($value, $_value, $_type);
        if($_value === null){
            if(!isset($parameters[$value]))
                throw new FrameworkException(sprintf("Could not resolve parameter '%s'", $value));
            $_name = $value;
            $__value = $parameters[$value];
            DataType::resolveBasicTypeAndValue($__value, $_value, $_type);
        }
        if($_value === null) return null;
        return new TemplateVariable($_name ?? "undefined", $_type, $_value);
    }
}
