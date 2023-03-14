<?php

namespace Os\Framework\Http\Client;

use JetBrains\PhpStorm\Pure;
use Os\Framework\Debug\Dumper;
use Os\Framework\Http\Response\HttpClientResponse;

class HttpClient
{
    public const GET = "get";
    public const POST = "post";
    public const PUT = "put";
    public const DELETE = "delete";

    protected HttpOptions $baseOptions;

    #[Pure]
    public function __construct(protected string $baseUrl, HttpOptions $baseOptions = null){
        $this->baseOptions = $baseOptions ?? new HttpOptions();
    }

    public function get(string $path, HttpOptions $options = null): HttpClientResponse|null
    {
        return $this->request(self::GET, $path, $options);
    }

    public function post(string $path, HttpOptions $options = null): HttpClientResponse|null
    {
        return $this->request(self::POST, $path, $options);
    }

    public function put(string $path, HttpOptions $options = null): HttpClientResponse|null
    {
        return $this->request(self::PUT, $path, $options);
    }

    public function delete(string $path, HttpOptions $options = null): HttpClientResponse|null
    {
        return $this->request(self::DELETE, $path, $options);
    }

    public function request(string $method, string $path, ?HttpOptions $options = null): HttpClientResponse|null
    {
        $_options = $options !== null ? $options->getOptions() : [];
        $request = $this->buildRequest($path, strtolower($method), $_options);
        if($request === null) return null;
        $content = curl_exec($request);
        $response = HttpClientResponse::createFrom(curl_getinfo($request), $content);
        curl_close($request);
        return $response;
    }

    protected function buildRequest(string $path, string $method = self::GET, array $options = []): null|\CurlHandle
    {
        $_options = array_merge_recursive($this->baseOptions->getOptions(), $options);
        $request = curl_init($this->buildUrl($path, $_options));
        if($request === false) return null;
        if(!empty($_options["headers"])){
            $parsedHeaders = [];
            foreach($_options["headers"] as $key => $value){
                $parsedHeaders[] = sprintf("%s: %s", $key, $value);
            }
            curl_setopt($request, CURLOPT_HTTPHEADER, $parsedHeaders);
        }
        if($method !== self::GET && !empty($_options["body"])){
            curl_setopt($request, CURLOPT_POSTFIELDS, $_options["body"]);
        }
        if($method !== self::GET && $method !== self::POST){
            curl_setopt($request, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        }
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);

        return $request;
    }

    protected function buildUrl(string $path, array $options): string
    {
        $baseUrl = $this->baseUrl;
        if(mb_substr($this->baseUrl, -1) !== "/")
            $baseUrl = sprintf("%s/", $baseUrl);
        $path = ltrim($path, "/");
        return sprintf("%s%s%s", $baseUrl, $path, $this->buildQuery($options));
    }

    public function buildQuery(array $options): string {
        if(!isset($options["query"])) return "";
        $query = [];
        foreach($options["query"] as $key => $value){
            $query[] = $key . "=" . $value;
        }
        return "?" . implode("&", $query);
    }
}

