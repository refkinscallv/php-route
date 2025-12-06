<?php
/**
 * --------------------------------------------------------------------------
 * refkinscallv/php-route
 * PHP Routing Library provides a flexible and easy-to-use routing system
 * Version: 1.0.7 | License: MIT
 * Author: Refkinscallv <refkinscallv@gmail.com>
 * --------------------------------------------------------------------------
 */

namespace RFRoute\Http;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;

class Request implements ServerRequestInterface
{
    private ServerRequestInterface $request;
    private array $parsedJson = [];
    private array $customAttributes = [];

    public function __construct()
    {
        $this->request = ServerRequest::fromGlobals();
        $contentType = strtolower($this->request->getHeaderLine('Content-Type'));

        if (str_contains($contentType, 'application/json')) {
            $raw = (string) $this->request->getBody();
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->parsedJson = $decoded ?? [];
                $this->request = $this->request->withParsedBody($this->parsedJson);
            }
        } elseif (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str((string) $this->request->getBody(), $form);
            $this->request = $this->request->withParsedBody($form);
        }
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        $body = $this->getParsedBody() ?? [];
        $query = $this->getQueryParams();
        $merged = array_merge($query, $body, $this->parsedJson);

        if ($key === null) return $merged;
        return $merged[$key] ?? $default;
    }

    public function only(array $keys): array
    {
        $data = $this->input();
        return array_intersect_key($data, array_flip($keys));
    }

    public function json(): array
    {
        return $this->parsedJson;
    }

    public function file(string $name): ?array
    {
        $files = $this->getUploadedFiles();
        return $files[$name] ?? null;
    }
    
    public function getAttributes(): array
    {
        return array_merge($this->request->getAttributes(), $this->customAttributes);
    }
    
    public function getAttribute($name, $default = null)
    {
        if (isset($this->customAttributes[$name])) {
            return $this->customAttributes[$name];
        }
        return $this->request->getAttribute($name, $default);
    }
    
    public function withAttribute($name, $value): ServerRequestInterface
    {
        $this->customAttributes[$name] = $value;
        $this->request = $this->request->withAttribute($name, $value);
        return $this;
    }

    public function withoutAttribute($name): ServerRequestInterface
    {
        unset($this->customAttributes[$name]);
        $this->request = $this->request->withoutAttribute($name);
        return $this;
    }

    public function getUri(): UriInterface { return $this->request->getUri(); }
    public function withUri(UriInterface $uri, $preserveHost = false): ServerRequestInterface { 
        $this->request = $this->request->withUri($uri, $preserveHost); 
        return $this; 
    }
    
    public function getMethod(): string { return $this->request->getMethod(); }
    public function withMethod($method): ServerRequestInterface { 
        $this->request = $this->request->withMethod($method); 
        return $this; 
    }
    
    public function getProtocolVersion(): string { return $this->request->getProtocolVersion(); }
    public function withProtocolVersion($version): ServerRequestInterface { 
        $this->request = $this->request->withProtocolVersion($version); 
        return $this; 
    }
    
    public function getHeaders(): array { return $this->request->getHeaders(); }
    public function hasHeader($name): bool { return $this->request->hasHeader($name); }
    public function getHeader($name): array { return $this->request->getHeader($name); }
    public function getHeaderLine($name): string { return $this->request->getHeaderLine($name); }
    
    public function withHeader($name, $value): ServerRequestInterface { 
        $this->request = $this->request->withHeader($name, $value); 
        return $this; 
    }
    
    public function withAddedHeader($name, $value): ServerRequestInterface { 
        $this->request = $this->request->withAddedHeader($name, $value); 
        return $this; 
    }
    
    public function withoutHeader($name): ServerRequestInterface { 
        $this->request = $this->request->withoutHeader($name); 
        return $this; 
    }
    
    public function getBody(): StreamInterface { return $this->request->getBody(); }
    public function withBody(StreamInterface $body): ServerRequestInterface { 
        $this->request = $this->request->withBody($body); 
        return $this; 
    }
    
    public function getServerParams(): array { return $this->request->getServerParams(); }
    public function getCookieParams(): array { return $this->request->getCookieParams(); }
    public function withCookieParams(array $cookies): ServerRequestInterface { 
        $this->request = $this->request->withCookieParams($cookies); 
        return $this; 
    }
    
    public function getQueryParams(): array { return $this->request->getQueryParams(); }
    public function withQueryParams(array $query): ServerRequestInterface { 
        $this->request = $this->request->withQueryParams($query); 
        return $this; 
    }
    
    public function getUploadedFiles(): array { return $this->request->getUploadedFiles(); }
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface { 
        $this->request = $this->request->withUploadedFiles($uploadedFiles); 
        return $this; 
    }
    
    public function getParsedBody(): array|null { return $this->request->getParsedBody(); }
    public function withParsedBody($data): ServerRequestInterface { 
        $this->request = $this->request->withParsedBody($data); 
        return $this; 
    }
    
    public function getRequestTarget(): string { return $this->request->getRequestTarget(); }
    public function withRequestTarget($requestTarget): ServerRequestInterface { 
        $this->request = $this->request->withRequestTarget($requestTarget); 
        return $this; 
    }
}
