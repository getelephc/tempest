<?php

declare(strict_types=1);

namespace Tempest\Http {
    final class Request
    {
        public string $method;
        public string $path;

        public function __construct()
        {
            $this->method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');

            $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
            $query = strpos($uri, '?');
            $this->path = $query === false ? $uri : substr($uri, 0, $query);
        }

        public function segment(int $index, string $default = ''): string
        {
            $current = 0;

            foreach (explode('/', $this->path) as $segment) {
                if ($segment === '') {
                    continue;
                }

                if ($current === $index) {
                    return $segment;
                }

                $current += 1;
            }

            return $default;
        }
    }

    final class Response
    {
        public int $status;
        public string $body;
        public array $headers = [];

        public function __construct(string $body = '', int $status = 200)
        {
            $this->body = $body;
            $this->status = $status;
        }

        public function withHeader(string $name, string $value): self
        {
            $this->headers[$name] = $value;

            return $this;
        }

        public static function html(string $body, int $status = 200): self
        {
            return (new self($body, $status))
                ->withHeader('Content-Type', 'text/html; charset=utf-8');
        }

        public static function text(string $body, int $status = 200): self
        {
            return (new self($body, $status))
                ->withHeader('Content-Type', 'text/plain; charset=utf-8');
        }

        public static function json(string $body, int $status = 200): self
        {
            return (new self($body, $status))
                ->withHeader('Content-Type', 'application/json');
        }

        public static function redirect(string $location): self
        {
            return (new self('', 302))->withHeader('Location', $location);
        }

        public function send(): void
        {
            http_response_code($this->status);

            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }

            header('X-Powered-By: Tempest-on-Elephc');
            echo $this->body;
        }
    }
}

namespace Tempest\Router {
    use Tempest\Http\Request;
    use Tempest\Http\Response;

    #[\Attribute(\Attribute::TARGET_METHOD)]
    final class Get
    {
        public string $uri;

        public function __construct(string $uri)
        {
            $this->uri = $uri;
        }
    }

    interface Handler
    {
        public function handle(Request $request): Response;
    }

    final class Route
    {
        public string $method;
        public string $pattern;
        public Handler $handler;

        public function __construct(string $method, string $pattern, Handler $handler)
        {
            $this->method = $method;
            $this->pattern = $pattern;
            $this->handler = $handler;
        }

        public function matches(Request $request): bool
        {
            if ($this->method !== $request->method) {
                return false;
            }

            $pattern = explode('/', $this->pattern);
            $path = explode('/', $request->path);

            if (count($pattern) !== count($path)) {
                return false;
            }

            for ($index = 0; $index < count($pattern); $index += 1) {
                $expected = (string) $pattern[$index];

                if (strlen($expected) > 0 && $expected[0] === ':') {
                    continue;
                }

                if ($expected !== (string) $path[$index]) {
                    return false;
                }
            }

            return true;
        }

        public function run(Request $request): Response
        {
            return $this->handler->handle($request);
        }
    }

    final class Router
    {
        private array $routes = [];

        public function get(string $pattern, Handler $handler): void
        {
            $this->routes[] = new Route('GET', $pattern, $handler);
        }

        public function dispatch(Request $request): Response
        {
            foreach ($this->routes as $route) {
                if ($route->matches($request)) {
                    return $route->run($request);
                }
            }

            return Response::text("404 Not Found\n", 404);
        }
    }

    final class HttpApplication
    {
        private Router $router;

        public function __construct(Router $router)
        {
            $this->router = $router;
        }

        public static function boot(string $_root): self
        {
            $router = new Router();

            // Static discovery manifest for the finite Elephc profile.
            $router->get('/', new \App\Controller\HomeController());
            $router->get('/health', new \App\Controller\HealthController());
            $router->get('/hello/:name', new \App\Controller\HelloController());
            $router->get('/elephc', new \App\Controller\ElephcController());

            return new self($router);
        }

        public function run(): void
        {
            $this->router->dispatch(new Request())->send();
        }
    }
}

namespace App\Controller {
    use Tempest\Http\Request;
    use Tempest\Http\Response;
    use Tempest\Router\Get;
    use Tempest\Router\Handler;

    final class HomeController implements Handler
    {
        #[Get('/')]
        public function handle(Request $_request): Response
        {
            return Response::html(<<<'HTML'
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tempest on Elephc</title>
    <style>
        :root { color-scheme: dark; font-family: ui-sans-serif, system-ui, sans-serif; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #111827; color: #f9fafb; }
        main { width: min(760px, calc(100% - 3rem)); padding: 3rem; border: 1px solid #374151; border-radius: 1.5rem; background: #1f2937; }
        h1 { margin: 0 0 1rem; font-size: clamp(2.2rem, 8vw, 5rem); letter-spacing: -.06em; }
        strong { color: #ff7a1a; }
        p { color: #d1d5db; line-height: 1.7; }
        code, a { color: #fbbf24; }
    </style>
</head>
<body>
<main>
    <h1>Tempest on <strong>Elephc</strong></h1>
    <p>This response comes from a native prefork web binary compiled from PHP.</p>
    <p>The AOT profile keeps Tempest-style controllers, route attributes, request and response objects, with a static discovery manifest.</p>
    <p>Try <a href="/health"><code>/health</code></a>, <a href="/hello/tempest"><code>/hello/tempest</code></a>, or <a href="/elephc"><code>/elephc</code></a>.</p>
</main>
</body>
</html>
HTML);
        }
    }

    final class HealthController implements Handler
    {
        #[Get('/health')]
        public function handle(Request $_request): Response
        {
            return Response::json('{"status":"ok","framework":"tempest","runtime":"elephc"}');
        }
    }

    final class HelloController implements Handler
    {
        #[Get('/hello/{name}')]
        public function handle(Request $request): Response
        {
            return Response::text('Hello, ' . $request->segment(1, 'world') . "!\n");
        }
    }

    final class ElephcController implements Handler
    {
        #[Get('/elephc')]
        public function handle(Request $_request): Response
        {
            return Response::redirect('https://elephc.dev');
        }
    }
}
