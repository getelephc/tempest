<?php

declare(strict_types=1);

namespace Elephc\TempestRuntime;

use App\ElephcController;
use App\HealthController;
use App\HelloController;
use App\HomeController;
use Tempest\Core\Middleware;
use Tempest\Http\Method;
use Tempest\Router\GenericRouter;
use Tempest\Router\HttpApplication;
use Tempest\Router\MatchRouteMiddleware;
use Tempest\Router\RouteConfig;
use Tempest\Router\Routing\Construction\DiscoveredRoute;
use Tempest\Router\Routing\Matching\MatchingRegex;
use Tempest\Router\Routing\Matching\GenericRouteMatcher;

final class Bootstrap
{
    public static function application(string $root): HttpApplication
    {
        $container = new StaticContainer();
        $routeConfig = self::routeConfig();
        $routeMatcher = new GenericRouteMatcher($routeConfig);
        $request = AotRequest::fromGlobals();

        $routeMiddleware = new MatchRouteMiddleware($routeMatcher, $container);
        $genericRouter = new GenericRouter($container, $routeConfig, $routeMiddleware);
        $responseSender = new AotResponseSender($request);
        $kernel = new AotKernel($root, $container);

        return new HttpApplication($container, $genericRouter, $request, $responseSender, $kernel);
    }

    private static function routeConfig(): RouteConfig
    {
        $home = new DiscoveredRoute('/', Method::GET, [], [], [], HomeController::class);
        $health = new DiscoveredRoute('/health', Method::GET, [], [], [], HealthController::class);
        $hello = new DiscoveredRoute('/hello/{name}', Method::GET, ['name'], [false], [], HelloController::class);
        $elephc = new DiscoveredRoute('/elephc', Method::GET, [], [], [], ElephcController::class);

        return new RouteConfig(
            staticRoutes: [
                $home,
                $health,
                $elephc,
            ],
            dynamicRoutes: [$hello],
            matchingRegexes: [new MatchingRegex(['/^\\/hello\\/([^\\/]+)\\/?$/'])],
            handlerIndex: [],
            middleware: new Middleware(),
        );
    }
}
