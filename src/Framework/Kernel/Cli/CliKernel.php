<?php

namespace Os\Framework\Kernel\Cli;

use Os\Framework\Cli\Application;
use Os\Framework\Cli\Exception\CommandNotFoundException;
use Os\Framework\Debug\Dumper;
use Os\Framework\Exception\FrameworkException;
use Throwable;

class CliKernel extends \Os\Framework\Kernel\Kernel implements \Os\Framework\Kernel\KernelInterface
{

    /**
     * @throws CommandNotFoundException
     * @throws FrameworkException|Throwable
     */
    protected function _render()
    {
        (new Application($this->container))->run();
    }

    protected function _done()
    {
        // TODO: Implement _done() method.
    }
}