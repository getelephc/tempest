<?php

declare(strict_types=1);

namespace Elephc\TempestRuntime;

use JsonSerializable;
use Stringable;
use Tempest\Http\Response;
use Tempest\Router\ResponseSender;

final readonly class AotResponseSender implements ResponseSender
{
    public function __construct(
        private AotRequest $request,
    ) {}

    public function send(Response $response): Response
    {
        header('X-Powered-By: Tempest-on-Elephc');

        foreach ($response->getHeaders() as $header) {
            if ($header instanceof \Tempest\Http\Header) {
                foreach ($header->values as $value) {
                    header("{$header->name}: {$value}", replace: false);
                }
            }
        }

        http_response_code($response->getStatusCode());

        if ($this->request->methodName === 'HEAD') {
            return $response;
        }

        $body = $response->getBody();

        if (is_string($body) && str_starts_with($body, '<!doctype html>')) {
            header('Content-Type: text/html; charset=utf-8');
        }

        if (is_array($body) || $body instanceof JsonSerializable) {
            echo json_encode($body);
        } elseif ($body instanceof Stringable || is_string($body)) {
            echo (string) $body;
        }

        return $response;
    }
}
