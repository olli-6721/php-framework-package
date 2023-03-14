<?php

namespace Os\Framework\Http\Response;

class HttpClientResponse extends Response
{
    protected ?string $scheme;
    protected ?string $url;
    protected ?string $redirectUrl;

    public static function createFrom(array $curlInfoData, ?string $content): static
    {
        $response = new static(empty($content) ? null : $content, $curlInfoData["http_code"] ?? 200);
        $response->setScheme($curlInfoData["scheme"] ?? null);
        $response->setUrl($curlInfoData["url"] ?? null);
        $response->setRedirectUrl($curlInfoData["redirect_url"] ?? null);

        return $response;
    }

    /**
     * @return string|null
     */
    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    /**
     * @param string|null $scheme
     */
    public function setScheme(?string $scheme): void
    {
        $this->scheme = $scheme;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     */
    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return string|null
     */
    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    /**
     * @param string|null $redirectUrl
     */
    public function setRedirectUrl(?string $redirectUrl): void
    {
        $this->redirectUrl = $redirectUrl;
    }
}