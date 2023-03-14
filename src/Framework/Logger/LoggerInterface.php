<?php

namespace Os\Framework\Logger;

interface LoggerInterface
{
    public static function getName(): string;

    public function log(string $message, LogLevel $level = LogLevel::INFO): void;
    public function debug(string $message): void;
    public function info(string $message): void;
    public function notice(string $message): void;
    public function warning(string $message): void;
    public function error(string $message): void;
    public function critical(string $message): void;
    public function emergency(string $message): void;
}