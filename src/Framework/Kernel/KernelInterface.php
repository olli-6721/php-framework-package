<?php

namespace Os\Framework\Kernel;

interface KernelInterface
{
    public static function build(): static;
    public function render();
    public function done();
}