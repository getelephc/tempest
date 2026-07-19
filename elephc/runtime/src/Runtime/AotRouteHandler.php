<?php

declare(strict_types=1);

namespace Elephc\TempestRuntime;

use RuntimeException;
use Tempest\Http\Response;
use Tempest\Router\MatchedRoute;

final readonly class AotRouteHandler
{
    public function __construct(
        private StaticContainer $container,
    ) {}

    public function handle(AotRequest $request, ?MatchedRoute $matchedRoute): Response
    {
        if (! $matchedRoute instanceof MatchedRoute) {
            throw new RuntimeException('No matched route was registered.');
        }

        return $this->container->dispatchController(
            $matchedRoute->handler,
            $matchedRoute->parameter,
        );
    }
}
