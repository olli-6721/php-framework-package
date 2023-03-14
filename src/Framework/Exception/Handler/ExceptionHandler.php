<?php

namespace Os\Framework\Exception\Handler;

use Os\Framework\Debug\Dumper;
use Os\Framework\DependencyInjection\ContainerInterface;
use Os\Framework\Template\Render\TemplateRenderer;

class ExceptionHandler
{
    public function __construct(\Throwable $e, ContainerInterface $container = null)
    {
        $message = "Activate dev mode to see error message";
        $trace = "";
        if(defined("ENV") && strtolower(ENV) === "dev"){
            $trace = $e->getTraceAsString();
            $message = sprintf("'%s', '%s' thrown on line %d in file %s", $e::class, $e->getMessage(), $e->getLine(), $e->getFile());
        }
        if($container === null){
            $this->renderBasic($e->getCode(), $message, $trace);
            die();
        }
        $container->get('file.logger')->error(sprintf("'%s', '%s' thrown on line %d in file %s", $e::class, $e->getMessage(), $e->getLine(), $e->getFile()));
        switch(CONTEXT){
            case "http":
                try {
                    $renderer = new TemplateRenderer($container);
                    $path = sprintf("%s%sTemplate%sInternal%sExceptionHandler%sbase.template.html",FRAMEWORK_BASE_PATH, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR);
                    echo $renderer->render(templateName: $path, parameters: ["code" => $e->getCode(), "message" => $message, "trace" => $trace], pathIsAbsolute: true);
                }
                catch (\Throwable $e){
                    echo sprintf("Template rendering failed! (%s)\n\r", $e->getMessage());
                    $this->renderBasic($e->getCode(), $message, $trace);
                }
                break;
            case "cli":
                $this->renderBasic($e->getCode(), $message, $trace);
                break;
        }
        die();
    }

    protected function renderBasic(int $code, string $message, string $trace){
        echo $code. "\n\r";
        echo $message. "\n\r";
        echo $trace;
    }
}
