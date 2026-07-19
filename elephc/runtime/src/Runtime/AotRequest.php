<?php

declare(strict_types=1);

namespace Elephc\TempestRuntime;

use Tempest\Http\Method;
use Tempest\Http\Request;

final class AotRequest implements Request
{
    public Method $method;

    public string $methodName;

    public string $uri;

    public ?string $raw;

    public array $body;

    public array $headers;

    public string $path;

    public array $query;

    public array $files;

    public array $cookies;

    public function __construct(Method $method, string $uri, array $body = [], array $headers = [], ?string $raw = null, string $methodName = 'GET')
    {
        $this->method = $method;
        $this->methodName = $methodName;
        $this->uri = $uri;
        $this->body = $body;
        $this->headers = $headers;
        $this->raw = $raw;
        $this->files = [];
        $this->cookies = [];

        $uriParts = explode('?', rawurldecode($uri));
        $this->path = $uriParts[0] === '' ? '/' : $uriParts[0];
        $this->query = [];
    }

    public static function fromGlobals(): self
    {
        $methodName = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $method = Method::tryFrom($methodName) ?? Method::GET;
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        return new self($method, $uri, [], [], null, $methodName);
    }

    public function has(string $key): bool
    {
        return isset($this->body[$key]) || isset($this->query[$key]);
    }

    public function getMethod(): Method
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function hasBody(?string $key = null): bool
    {
        return $key === null ? $this->body !== [] : isset($this->body[$key]);
    }

    public function hasQuery(string $key): bool
    {
        return isset($this->query[$key]);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function getSessionValue(string $name): mixed
    {
        return null;
    }

    public function getCookie(string $name): mixed
    {
        return $this->cookies[$name] ?? null;
    }

    public function accepts(mixed ...$contentType): bool
    {
        return true;
    }

    public function withMethod(Method $method): self
    {
        $request = clone $this;
        $request->method = $method;

        return $request;
    }
}
