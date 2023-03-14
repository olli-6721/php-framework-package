<?php

namespace Os\Framework\Config;

use Exception;
use Os\Framework\Config\Exception\ConfigFileNotFoundException;
use Os\Framework\Config\Exception\ConfigFileParsingException;
use Os\Framework\Config\Exception\EnvFileNotFoundException;
use Os\Framework\Config\Exception\NoBundleConfigurationFoundException;
use Os\Framework\Config\Exception\NoBundleFieldConfigurationFoundException;
use Os\Framework\Debug\Dumper;
use Os\Framework\Exception\FrameworkException;
use Os\Framework\Filesystem\Exception\FileNotFoundException;
use Os\Framework\Filesystem\Exception\FileReadingException;
use Os\Framework\Filesystem\Exception\NoFileExtensionFoundException;
use Os\Framework\Filesystem\Filesystem;

class ConfigReader
{
    public const CONFIG_FILE_DEST = BASE_PATH.DIRECTORY_SEPARATOR."config".DIRECTORY_SEPARATOR."config.yml";

    protected string $APP_ENV;

    protected ?array $config = null;
    protected ?string $configFileName = null;
    protected ?array $env = null;
    protected Filesystem $filesystem;

    /**
     * @throws NoFileExtensionFoundException
     * @throws ConfigFileParsingException
     * @throws FileNotFoundException
     * @throws FileReadingException
     * @throws EnvFileNotFoundException
     * @throws ConfigFileNotFoundException
     */
    public function __construct(){
        $this->filesystem = new Filesystem();
        $this->APP_ENV = "dev";
        $this->loadConfigFile();
    }

    /**
     * @throws NoFileExtensionFoundException
     * @throws ConfigFileParsingException
     * @throws FileNotFoundException
     * @throws FileReadingException
     * @throws EnvFileNotFoundException
     * @throws ConfigFileNotFoundException
     */
    public function reloadConfigFile(){
        $this->loadConfigFile();
    }

    /**
     * @throws FileReadingException
     * @throws NoFileExtensionFoundException
     * @throws ConfigFileParsingException
     * @throws NoBundleConfigurationFoundException
     * @throws NoBundleFieldConfigurationFoundException
     * @throws FileNotFoundException
     * @throws ConfigFileNotFoundException
     * @throws EnvFileNotFoundException
     * @deprecated
     */
    public function read(string $bundleName = null, array $requiredFields = []): array
    {
        if($this->config === null)
            $this->loadConfigFile();

        if(!isset($this->config["framework"]))
            return [];
        if($bundleName !== null){
            if(!isset($this->config["framework"][$bundleName]))
                throw new NoBundleConfigurationFoundException($bundleName);
            foreach($requiredFields as $field){
                if(!isset($this->config["framework"][$bundleName][$field]))
                    throw new NoBundleFieldConfigurationFoundException($bundleName, $field);
            }
        }
        return $bundleName !== null ? ($this->config["framework"][$bundleName] ?? []) : $this->config["framework"];
    }

    /**
     * @throws ConfigFileParsingException
     * @throws FileNotFoundException
     * @throws FileReadingException
     * @throws NoFileExtensionFoundException
     * @throws EnvFileNotFoundException
     * @throws ConfigFileNotFoundException
     */
    public function readPath(string $path): string|array|bool|int|float|null
    {
        if($this->config === null)
            $this->loadConfigFile();
        if(!isset($this->config["framework"]))
            return null;
        if(str_starts_with($path, "framework") && strlen($path) > 9)
            $path = substr($path, 10);
        $pathParts = explode(".", $path);
        $_configItem = $this->config["framework"];
        foreach($pathParts as $part){
            if(!isset($_configItem[$part])) return null;
            $_configItem = $_configItem[$part];
        }
        return $_configItem;
    }

    /**
     * @throws NoFileExtensionFoundException
     * @throws ConfigFileParsingException
     * @throws FileNotFoundException
     * @throws FileReadingException
     * @throws EnvFileNotFoundException
     * @throws ConfigFileNotFoundException
     */
    protected function loadConfigFile(){
        $this->loadEnvFile();
        
        $configFilePath = $this->configFileName ?? self::CONFIG_FILE_DEST;
        if(!$this->filesystem->fileExists($configFilePath))
            throw new ConfigFileNotFoundException($configFilePath);

        $fileNameParts = explode(".", $configFilePath);
        if(count($fileNameParts) < 2) throw new NoFileExtensionFoundException($configFilePath);
        $fileExtension = $fileNameParts[array_key_last($fileNameParts)];

        $fileContent = $this->filesystem->read($configFilePath);
        $this->loadEnvVariables($fileContent);

        $this->config = match($fileExtension){
            "yml", "yaml" => self::parseYaml($fileContent),
            "json" => self::parseJson($fileContent),
            "env" => self::parseEnv($fileContent),
            default => throw new ConfigFileParsingException($configFilePath)
        };
    }

    /**
     * @throws EnvFileNotFoundException
     * @throws FileNotFoundException
     * @throws FileReadingException
     */
    protected function loadEnvFile(){
        $envPath = sprintf("%s%s.env", BASE_PATH, DIRECTORY_SEPARATOR);
        if(!$this->filesystem->fileExists($envPath))
            throw new EnvFileNotFoundException();
        $this->env = $this->parseEnv($this->filesystem->read($envPath));
        if(isset($this->env["CONFIG_FILE"]) && is_string($this->env["CONFIG_FILE"])){
            $realPath = realpath(sprintf("%s%s%s", BASE_PATH, DIRECTORY_SEPARATOR, $this->env["CONFIG_FILE"]));
            $this->configFileName = $realPath === false ? null : $realPath;
        }
        if(isset($this->env["APP_ENV"]) && is_string($this->env["APP_ENV"])){
            $this->APP_ENV = $this->env["APP_ENV"];
        }
    }

    protected function loadEnvVariables(string &$configContent){
        $regex = '(([^\'"])(%(.*)%)([^\'"]))';
        $matches = null;
        preg_match_all($regex, $configContent, $matches);
        if($matches === null || count($matches) < 5 || !is_array($matches[3])) return;
        foreach($matches[3] as $match){
            if(!isset($this->env[$match])) continue;
            $configContent = preg_replace(str_replace(".*", $match, $regex), sprintf("$1%s$4", $this->env[$match]), $configContent);
        }
    }

    protected function parseEnv(string $env): array
    {
        $config = [];
        foreach(preg_split("/((\r?\n)|(\r\n?))/", $env) as $line){
            $data = explode("=", $line);
            if(count($data) < 2) continue;
            $key = $data[0];
            $value = $data[1];
            $config[$key] = $value;
        }
        return $config;
    }

    protected function parseYaml(string $yaml): array
    {
        return \yaml_parse($yaml);
    }

    protected function parseJson(string $yaml): array
    {
        return \json_decode($yaml, true);
    }

    public function getAppEnv(): string
    {
        return $this->APP_ENV;
    }
}