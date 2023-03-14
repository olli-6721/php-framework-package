<?php
namespace Os\Framework\Http\Controller;

use Os\Framework\DependencyInjection\ContainerInterface;
use Os\Framework\Http\Response\Response;
use Os\Framework\Template\Render\TemplateRenderer;

abstract class AbstractController
{
    protected TemplateRenderer $templateRenderer;

    public function __construct(protected ContainerInterface $container)
    {
        $this->templateRenderer = new TemplateRenderer($this->container);
    }

    /**
     * @throws \Exception
     */
    final public function render(string $template, array $parameters = []): Response
    {
        return new Response($this->templateRenderer->render($template, $parameters));
    }
}