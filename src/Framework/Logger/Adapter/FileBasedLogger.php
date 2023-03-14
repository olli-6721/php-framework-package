<?php

namespace Os\Framework\Logger\Adapter;

use Os\Framework\Config\ConfigReader;
use Os\Framework\Filesystem\Filesystem;
use Os\Framework\Logger\LogLevel;

class FileBasedLogger implements \Os\Framework\Logger\LoggerInterface
{
    protected Filesystem $fs;
    protected string $logDir;
    protected string $filename;
    protected bool $addDate;

    public function __construct(protected ConfigReader $configReader)
    {
        $this->fs = new Filesystem();
        $basePath = sprintf("logger.filenames.%s", strtoupper(ENV));
        $this->logDir = Filesystem::buildPath(BASE_PATH, $this->configReader->readPath("logger.directory"));
        $this->filename = $this->configReader->readPath(sprintf("%s.filename", $basePath));
        $this->addDate = $this->configReader->readPath(sprintf("%s.add_date", $basePath));
        if(!$this->fs->directoryExists($this->logDir))
            $this->fs->createDirectory($this->logDir);
    }

    public static function getName(): string
    {
        return "file";
    }

    public function log(string $message, LogLevel $level = LogLevel::INFO): void
    {
        if(strtoupper(ENV) === "PROD") {
            if(
                $level === LogLevel::DEBUG ||
                $level === LogLevel::NOTICE ||
                $level === LogLevel::WARNING ||
                $level === LogLevel::INFO
            )
                return;
        }
        $now = new \DateTime();
        if(!$this->addDate)
            $filename = sprintf("%s.log", $this->filename);
        else
            $filename = sprintf("%s-%s.log", $this->filename, $now->format("Y-m-d"));
        $filename = Filesystem::buildPath($this->logDir, $filename);
        if(!$this->fs->fileExists($filename))
            $this->fs->touch($filename);
        $this->fs->write($filename, sprintf("[%s][%s] %s\n", strtoupper($level->name), $now->format("c"), $message), "a+");
    }

    public function debug(string $message): void
    {
        $this->log($message, LogLevel::DEBUG);
    }

    public function info(string $message): void
    {
        $this->log($message);
    }

    public function notice(string $message): void
    {
        $this->log($message, LogLevel::NOTICE);
    }

    public function warning(string $message): void
    {
        $this->log($message, LogLevel::WARNING);
    }

    public function error(string $message): void
    {
        $this->log($message, LogLevel::ERROR);
    }

    public function critical(string $message): void
    {
        $this->log($message, LogLevel::CRITICAL);
    }

    public function emergency(string $message): void
    {
        $this->log($message, LogLevel::EMERGENCY);
    }
}