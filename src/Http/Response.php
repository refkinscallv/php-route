<?php

namespace RFRoute\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

class Response implements ResponseInterface
{
    private GuzzleResponse $response;

    public function __construct()
    {
        $this->response = new GuzzleResponse();
    }

    // PSR-7 helpers
    public function json($data, int $status = 200): static
    {
        $this->response = $this->response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
        echo json_encode($data);
        return $this;
    }

    public function send(string $data, int $status = 200): static
    {
        $this->response = $this->response->withStatus($status);
        echo $data;
        return $this;
    }

    public function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }

    // PSR-7 methods
    public function getStatusCode(): int { return $this->response->getStatusCode(); }
    public function withStatus($code, $reasonPhrase = ''): static {
        $clone = clone $this;
        $clone->response = $this->response->withStatus($code, $reasonPhrase);
        return $clone;
    }
    public function getReasonPhrase(): string { return $this->response->getReasonPhrase(); }
    public function getProtocolVersion(): string { return $this->response->getProtocolVersion(); }
    public function withProtocolVersion($version): static {
        $clone = clone $this;
        $clone->response = $this->response->withProtocolVersion($version);
        return $clone;
    }
    public function getHeaders(): array { return $this->response->getHeaders(); }
    public function hasHeader($name): bool { return $this->response->hasHeader($name); }
    public function getHeader($name): array { return $this->response->getHeader($name); }
    public function getHeaderLine($name): string { return $this->response->getHeaderLine($name); }
    public function withHeader($name, $value): static { 
        $clone = clone $this;
        $clone->response = $this->response->withHeader($name, $value);
        return $clone;
    }
    public function withAddedHeader($name, $value): static {
        $clone = clone $this;
        $clone->response = $this->response->withAddedHeader($name, $value);
        return $clone;
    }
    public function withoutHeader($name): static {
        $clone = clone $this;
        $clone->response = $this->response->withoutHeader($name);
        return $clone;
    }
    public function getBody(): StreamInterface { return $this->response->getBody(); }
    public function withBody(StreamInterface $body): static {
        $clone = clone $this;
        $clone->response = $this->response->withBody($body);
        return $clone;
    }
}
