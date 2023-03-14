<?php

namespace Os\Framework\Http\Request;

use Os\Framework\Debug\Dumper;

class Request
{
    protected string $method;
    protected string $baseUrl;
    protected string $path;
    protected string $uri;
    protected array $headers;
    protected array $query;

    /**
     * @throws \Exception
     */
    public function __construct(){
        if(
            !isset($_SERVER["REQUEST_METHOD"]) ||
            !isset($_SERVER["SERVER_NAME"]) ||
            !isset($_SERVER["REQUEST_URI"]) ||
            !isset($_SERVER["QUERY_STRING"])
        )
            throw new \Exception("Not all required request parameters transmitted");
        $this->method = strtolower($_SERVER["REQUEST_METHOD"]);
        $this->baseUrl = $_SERVER["SERVER_NAME"];
        $this->path = $_SERVER["REDIRECT_URL"];
        $this->uri = $_SERVER["REQUEST_URI"];
        $this->headers = [];
        $this->query = self::parseQueryString($_SERVER["QUERY_STRING"]);
    }

    public static function parseQueryString(string $queryString): array
    {
        $query = [];
        $parts = explode("&", $queryString);
        foreach($parts as $part){
            $_parts = explode("=", $part);
            $key = $_parts[0] ?? null;
            $value = $_parts[1] ?? "";
            if($key === null) continue;
            $query[$key] = $value;
        }
        return $query;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    public function hasHeader(string $key): bool
    {
        return isset($this->headers[$key]);
    }

    public function getHeader(string $key): null|bool|string|int|float|array
    {
        return $this->headers[$key] ?? null;
    }

    public function hasQueryParam(string $key): bool
    {
        return isset($this->query[$key]);
    }

    public function getQueryParam(string $key): null|bool|string|int|float|array
    {
        return $this->query[$key] ?? null;
    }
}