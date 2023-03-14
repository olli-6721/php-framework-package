<?php

namespace Os\Framework\Cli\Input\Argument;

use Os\Framework\Cli\Exception\NoArgumentsProvidedException;
use Os\Framework\Debug\Dumper;
use Os\Framework\Cli\Input\InputData;

class ArgumentHandler
{
    protected array $tokens;

    public function __construct(){
        $argv = $_SERVER["argv"];
        array_shift($argv);
        $this->tokens = $argv;
    }

    /**
     * @throws NoArgumentsProvidedException
     */
    public function parse(): InputData
    {
        $tokens = $this->tokens;
        if(empty($tokens))
            throw new NoArgumentsProvidedException();
        $commandName = $tokens[array_key_first($tokens)];
        $arguments = [];
        array_shift($tokens);
        $key = false;
        $keyValue = null;
        foreach($tokens as $token){
            if($key === false){
                if(str_starts_with($token, "--")){
                    $key = true;
                    $keyValue = substr($token, 2);
                }
                else {
                    $arguments[] = $token;
                }
                continue;
            }
            if($key === true && $keyValue !== null){
                $arguments[$keyValue] = $token;
                $keyValue = null;
                $key = false;
            }
        }
        return new InputData($commandName, $arguments);
    }
}