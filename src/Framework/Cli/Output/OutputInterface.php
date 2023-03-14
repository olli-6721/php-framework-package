<?php

namespace Os\Framework\Cli\Output;

interface OutputInterface
{
    public function write(string $message);

    public function writeLine(string $message);

    public function writeError(\Throwable|string $error);

    public function writeSuccess(string $message);

    public function startList(string $headline);

    public function addListItem(string $content, bool $noLineBreak = false);

    public function endList();
}