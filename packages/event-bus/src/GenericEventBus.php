<?php

declare(strict_types=1);

namespace Tempest\EventBus;

use Closure;
use Tempest\Container\Container;
use Tempest\Support\Str;
use UnitEnum;

use function Tempest\Reflection\reflect;

final readonly class GenericEventBus implements EventBus
{
    public function __construct(
        private Container $container,
        public EventBusConfig $eventBusConfig,
    ) {}

    public function listen(Closure $handler, string|UnitEnum|null $event = null): void
    {
        $this->eventBusConfig->addClosureHandler($handler, $event);
    }

    public function dispatch(string|object $event): void
    {
        $eventHandlers = $this->resolveHandlers($event);

        $dispatch = $this->getCallable($eventHandlers);

        $dispatch($event);
    }

    /** @return \Tempest\EventBus\CallableEventHandler[] */
    private function resolveHandlers(string|object $event): array
    {
        $eventName = Str\parse($event) ?: get_class($event);

        if ($event instanceof UnitEnum) {
            $eventName = get_class($event) . '::' . $eventName;
        }

        $handlers = [
            ...($this->eventBusConfig->handlers[$eventName] ?? []),
            ...($this->eventBusConfig->closureHandlers[$eventName] ?? []),
        ];

        if (is_object($event)) {
            $interfaces = class_implements($event);

            foreach ($interfaces as $interface) {
                $handlers = [
                    ...$handlers,
                    ...($this->eventBusConfig->handlers[$interface] ?? []),
                    ...($this->eventBusConfig->closureHandlers[$interface] ?? []),
                ];
            }
        }

        return $handlers;
    }

    /** @param \Tempest\EventBus\CallableEventHandler[] $eventHandlers */
    private function getCallable(array $eventHandlers): EventBusMiddlewareCallable
    {
        $callable = new EventBusMiddlewareCallable(function (string|object $event) use ($eventHandlers): void {
            $eventStopsPropagation = is_object($event) && reflect($event)->hasAttribute(StopsPropagation::class);

            foreach ($eventHandlers as $eventHandler) {
                $callable = $eventHandler->normalizeCallable($this->container);

                $callable($event);

                if ($eventStopsPropagation || ($eventHandler->handler->handler ?? null)?->hasAttribute(StopsPropagation::class)) {
                    break;
                }
            }
        });

        $middleware = $this->eventBusConfig->middleware;

        foreach ($middleware->unwrap() as $middlewareClass) {
            $callable = new EventBusMiddlewareCallable(fn (string|object $event) => $this->container->invoke(
                $middlewareClass,
                event: $event,
                next: $callable,
            ));
        }

        return $callable;
    }
}
