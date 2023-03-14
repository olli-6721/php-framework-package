<?php

namespace Os\Framework\Filesystem\FileMaker;

use JetBrains\PhpStorm\Pure;
use Os\Framework\Debug\Dumper;
use Os\Framework\Filesystem\FileDefinition\Php\PhpClassDefinition;

class PhpFileMaker extends AbstractFileMaker
{
    public const VISIBILITY_PUBLIC = "public";
    public const VISIBILITY_PROTECTED = "protected";
    public const VISIBILITY_PRIVATE = "private";

    public function __construct(protected string $absolutePath, protected PhpClassDefinition $class)
    {
    }

    public static function getExtension(): string
    {
        return "php";
    }

    #[Pure]
    public function getFileName(): string
    {
        return $this->class->getClassName();
    }

    public function getAbsolutePath(): string
    {
        return $this->absolutePath;
    }

    #[Pure]
    protected function generateContent(): string
    {

        $content = sprintf("<?php \nnamespace %s; ", $this->class->getNamespace());
        foreach($this->class->getDependencies() as $className){
            $content = sprintf("%s \nuse %s;", $content, $className);
        }
        if($this->class->getAnnotations() !== null)
            $content = sprintf("%s\n\n%s\nclass %s", $content, $this->class->getAnnotations(), $this->class->getClassName());
        else
            $content = sprintf("%s\n\nclass %s", $content, $this->class->getClassName());
        if($this->class->getExtends() !== null)
            $content = sprintf("%s extends \\%s", $content, $this->class->getExtends());
        $content = sprintf("%s {\n\n", $content);

        foreach($this->class->getProperties() as $property){
            foreach($property->getAttributes() as $attribute){
                $content = sprintf("%s    #[%s]\n", $content, $attribute);
            }

            $content = sprintf("%s    %s ", $content, $property->getVisibility());
            if($property->isNullable() === true)
                $content = sprintf("%s?", $content);
            $content = sprintf("%s%s $%s;\n\n", $content, $property->getType()->getPhpType(), $property->getName());
        }

        foreach($this->class->getFunctions() as $function){
            foreach($function->getAttributes() as $attribute){
                $content = sprintf("%s    #[%s]\n", $content, $attribute);
            }
            $content = sprintf("%s    %s", $content, $function->getVisibility());
            if($function->isStatic() === true)
                $content = sprintf("%s static", $content);
            $content = sprintf("%s function %s(", $content, $function->getName());
            foreach($function->getArguments() as $key => $argument){
                $content = match(true){
                    ($argument->getDataType() !== null && array_key_last($function->getArguments()) !== $key) => sprintf("%s%s $%s, ", $content, $argument->getDataType()->getPhpType(), $argument->getName()),
                    ($argument->getDataType() === null && array_key_last($function->getArguments()) !== $key) => sprintf("%s$%s, ", $content, $argument->getName()),
                    ($argument->getDataType() !== null && array_key_last($function->getArguments()) === $key) => sprintf("%s%s $%s", $content, $argument->getDataType()->getPhpType(), $argument->getName()),
                    default => sprintf("%s$%s", $content, $argument->getName())
                };
            }
            $content = sprintf("%s)", $content);
            if($function->getResponseType() !== null)
                $content = sprintf("%s: %s\n    ", $content, $function->getResponseType()->getPhpType());
            $content = sprintf("%s{\n       %s\n    }\n\n", $content, $function->getContent());
        }
        return sprintf("%s\n}", $content);
    }
}