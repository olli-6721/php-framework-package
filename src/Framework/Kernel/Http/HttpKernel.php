<?php

namespace Os\Framework\Kernel\Http;

use Os\Framework\Exception\FrameworkException;
use Os\Framework\Http\Request\Request;
use Os\Framework\Http\Routing\Router;
use Os\Framework\Kernel\Kernel;
use Os\Framework\Kernel\KernelInterface;

class HttpKernel extends Kernel implements KernelInterface
{
    protected Request $request;
    protected Router $router;

    /**
     * @throws FrameworkException
     */
    protected function ___construct(){
        $this->request = new Request();
        parent::___construct();
        $this->router = new Router($this->container);
    }

    public function _render()
    {
        $this->response = $this->router->resolve($this->request);
        echo $this->response->getContent();
    }

    protected function _done()
    {
        // TODO: Implement _done() method.
    }
}