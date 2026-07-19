<?php

declare(strict_types=1);

namespace Elephc\TempestRuntime;

use App\ElephcController;
use App\HealthController;
use App\HelloController;
use App\HomeController;
use RuntimeException;
use Tempest\Container\Container;
use Tempest\Core\Kernel;
use Tempest\Http\Response;
use Tempest\Router\MatchedRoute;
use Tempest\Router\MatchRouteMiddleware;
use Tempest\Router\ResponseSender;
use Tempest\Router\Router;

final class StaticContainer implements Container
{
    private mixed $request = null;

    private mixed $routeMiddleware = null;

    private mixed $matchedRoute = null;

    private mixed $router = null;

    private mixed $responseSender = null;

    private mixed $kernel = null;

    public function register(string $className, callable $definition): Container
    {
        throw new RuntimeException('Lazy definitions are unavailable in the finite Elephc profile.');
    }

    public function unregister(string $className, bool $tagged = false): Container
    {
        if ($className === MatchedRoute::class) {
            $this->matchedRoute = null;
        }

        return $this;
    }

    public function singleton(string $className, mixed $definition): Container
    {
        if ($className === AotRequest::class) {
            $this->request = $definition;
        } elseif ($className === MatchRouteMiddleware::class) {
            $this->routeMiddleware = $definition;
        } elseif ($className === MatchedRoute::class) {
            $this->matchedRoute = $definition;
        } elseif ($className === Router::class) {
            $this->router = $definition;
        } elseif ($className === ResponseSender::class) {
            $this->responseSender = $definition;
        } elseif ($className === Kernel::class) {
            $this->kernel = $definition;
        } else {
            throw new RuntimeException("Unsupported static container entry: {$className}.");
        }

        return $this;
    }

    public function config(object $config): Container
    {
        return $this->singleton($config::class, $config);
    }

    public function get(string $className): mixed
    {
        if ($className === AotRequest::class && $this->request !== null) {
            return $this->request;
        }

        if ($className === MatchRouteMiddleware::class && $this->routeMiddleware !== null) {
            return $this->routeMiddleware;
        }

        if ($className === MatchedRoute::class && $this->matchedRoute !== null) {
            return $this->matchedRoute;
        }

        if ($className === Router::class && $this->router !== null) {
            return $this->router;
        }

        if ($className === ResponseSender::class && $this->responseSender !== null) {
            return $this->responseSender;
        }

        if ($className === Kernel::class && $this->kernel !== null) {
            return $this->kernel;
        }

        throw new RuntimeException("No static container entry for {$className}.");
    }

    public function has(string $className): bool
    {
        if ($className === AotRequest::class) {
            return $this->request !== null;
        }

        if ($className === MatchRouteMiddleware::class) {
            return $this->routeMiddleware !== null;
        }

        if ($className === MatchedRoute::class) {
            return $this->matchedRoute !== null;
        }

        if ($className === Router::class) {
            return $this->router !== null;
        }

        if ($className === ResponseSender::class) {
            return $this->responseSender !== null;
        }

        if ($className === Kernel::class) {
            return $this->kernel !== null;
        }

        return false;
    }

    public function dispatchController(string $controllerClass, string $parameter): Response
    {
        if ($controllerClass === HomeController::class) {
            return (new HomeController())->__invoke();
        }

        if ($controllerClass === HealthController::class) {
            return (new HealthController())->__invoke();
        }

        if ($controllerClass === ElephcController::class) {
            return (new ElephcController())->__invoke();
        }

        if ($controllerClass === HelloController::class) {
            return (new HelloController())->__invoke($parameter === '' ? 'world' : $parameter);
        }

        throw new RuntimeException("No AOT controller dispatcher for {$controllerClass}.");
    }

    public function invoke(mixed $method, mixed ...$params): mixed
    {
        throw new RuntimeException('Unsupported static container invocation.');
    }

    public function addInitializer(string $initializerClass): Container
    {
        return $this;
    }

    public function addDecorator(string $decoratorClass, string $decoratedClass): Container
    {
        return $this;
    }

    public function addResettable(string $resettableClass): Container
    {
        return $this;
    }

    public function reset(): Container
    {
        return $this;
    }
}
