<?php

namespace Os\Framework\Cli\Output;

use Os\Framework\Exception\FrameworkException;

class OutputHandler implements OutputInterface
{

    protected ?string $lastListHeadline;

    public function __construct(){
        $this->lastListHeadline = null;
    }

    public function write(string $message)
    {
        echo $message;
    }

    public function writeLine(string $message)
    {
        $this->write($message);
        echo "\n\r";
    }

    public function writeError(\Throwable|string $error)
    {
        $message = $error;
        if($message instanceof \Throwable)
            $message = sprintf("'%s' thrown on line %d in file %s", $error->getMessage(), $error->getLine(), $error->getFile());
        $message = sprintf("[ERROR] %s", $message);
        $this->writeLine($this->getSectionDelimiter(strlen($message)));
        $this->writeLine($message);
        $this->writeLine($this->getSectionDelimiter(strlen($message)));
    }

    public function writeSuccess(string $message)
    {
        $message = sprintf("[SUCCESS] %s", $message);
        $this->writeLine($this->getSectionDelimiter(strlen($message)));
        $this->writeLine($message);
        $this->writeLine($this->getSectionDelimiter(strlen($message)));
    }

    protected function getSectionDelimiter(int $messageLength): string
    {
        $messageLength = $messageLength -1;
        $delimiter = "[";
        $i = 0;
        while($i < $messageLength){
            $delimiter = sprintf("%s-", $delimiter);
            $i++;
        }
        return sprintf("%s]", $delimiter);
    }

    public function startList(string $headline)
    {
        $this->lastListHeadline = sprintf("[----- %s -----]", $headline);
        $this->writeLine($this->lastListHeadline);
    }

    /**
     * @throws FrameworkException
     */
    public function addListItem(string $content, bool $noLineBreak = false)
    {
        if($this->lastListHeadline === null)
            throw new FrameworkException("No active list");
        if($noLineBreak === false)
            $this->writeLine(sprintf(": %s", $content));
        else
            $this->write(sprintf(": %s", $content));
    }

    /**
     * @throws FrameworkException
     */
    public function endList()
    {
        if($this->lastListHeadline === null)
            throw new FrameworkException("No active list");
        $this->writeLine($this->getSectionDelimiter(strlen($this->lastListHeadline) - 1));
        $this->lastListHeadline = null;
    }
}