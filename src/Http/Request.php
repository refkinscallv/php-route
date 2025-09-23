<?php

namespace RFRoute\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\ServerRequest;

class Request implements ServerRequestInterface
{
    private ServerRequest $request;

    public function __construct()
    {
        $this->request = ServerRequest::fromGlobals();
    }

    // URI
    public function getUri(): UriInterface { return $this->request->getUri(); }
    public function withUri(UriInterface $uri, $preserveHost = false): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->request = $this->request->withUri($uri, $preserveHost);
        return $clone;
    }

    // Method
    public function getMethod(): string { return $this->request->getMethod(); }
    public function withMethod($method): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->request = $this->request->withMethod($method);
        return $clone;
    }

    // Protocol
    public function getProtocolVersion(): string { return $this->request->getProtocolVersion(); }
    public function withProtocolVersion($version): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->request = $this->request->withProtocolVersion($version);
        return $clone;
    }

    // Headers
    public function getHeaders(): array { return $this->request->getHeaders(); }
    public function hasHeader($name): bool { return $this->request->hasHeader($name); }
    public function getHeader($name): array { return $this->request->getHeader($name); }
    public function getHeaderLine($name): string { return $this->request->getHeaderLine($name); }
    public function withHeader($name, $value): ServerRequestInterface { 
        $clone = clone $this;
        $clone->request = $this->request->withHeader($name, $value);
        return $clone;
    }
    public function withAddedHeader($name, $value): ServerRequestInterface {
        $clone = clone $this;
        $clone->request = $this->request->withAddedHeader($name, $value);
        return $clone;
    }
    public function withoutHeader($name): ServerRequestInterface {
        $clone = clone $this;
        $clone->request = $this->request->withoutHeader($name);
        return $clone;
    }

    // Body
    public function getBody(): StreamInterface { return $this->request->getBody(); }
    public function withBody(StreamInterface $body): ServerRequestInterface {
        $clone = clone $this;
        $clone->request = $this->request->withBody($body);
        return $clone;
    }

    // Server params
    public function getServerParams(): array { return $this->request->getServerParams(); }

    // Cookie
    public function getCookieParams(): array { return $this->request->getCookieParams(); }
    public function withCookieParams(array $cookies): ServerRequestInterface {
        $clone = clone $this;
        $clone->request = $this->request->withCookieParams($cookies);
        return $clone;
    }

    // Query
    public function getQueryParams(): array { return $this->request->getQueryParams(); }
    public function withQueryParams(array $query): ServerRequestInterface {
        $clone = clone $this;
        $clone->request = $this->request->withQueryParams($query);
        return $clone;
    }

    // Uploaded files
    public function getUploadedFiles(): array { return $this->request->getUploadedFiles(); }
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface {
        $clone = clone $this;
        $clone->request = $this->request->withUploadedFiles($uploadedFiles);
        return $clone;
    }

    // Parsed body
    public function getParsedBody(): array|null { return $this->request->getParsedBody(); }
    public function withParsedBody($data): ServerRequestInterface {
        $clone = clone $this;
        $clone->request = $this->request->withParsedBody($data);
        return $clone;
    }

    // Attributes
    public function getAttributes(): array { return $this->request->getAttributes(); }
    public function getAttribute($name, $default = null) { return $this->request->getAttribute($name, $default); }
    public function withAttribute($name, $value): ServerRequestInterface {
        $clone = clone $this;
        $clone->request = $this->request->withAttribute($name, $value);
        return $clone;
    }
    public function withoutAttribute($name): ServerRequestInterface {
        $clone = clone $this;
        $clone->request = $this->request->withoutAttribute($name);
        return $clone;
    }

    // Request target
    public function getRequestTarget(): string { return $this->request->getRequestTarget(); }
    public function withRequestTarget($requestTarget): ServerRequestInterface {
        $clone = clone $this;
        $clone->request = $this->request->withRequestTarget($requestTarget);
        return $clone;
    }
}
