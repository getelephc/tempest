<?php

declare(strict_types=1);

namespace Elephc\TempestRuntime;

use Tempest\Container\Container;
use Tempest\Core\Kernel;

final class AotKernel implements Kernel
{
    public string $root;

    public string $internalStorage;

    public Container $container;

    public function __construct(string $root, Container $container)
    {
        $this->root = $root;
        $this->internalStorage = $root . '/.tempest';
        $this->container = $container;
    }

    public static function boot(
        string $root,
        array $discoveryLocations = [],
        ?Container $container = null,
        ?string $internalStorage = null,
    ): Kernel {
        $kernel = new self($root, $container ?? new StaticContainer());

        if ($internalStorage !== null) {
            $kernel->internalStorage = (string) $internalStorage;
        }

        return $kernel;
    }

    public function shutdown(int|string $status = ''): void
    {
        // Elephc reruns the entry point for every web request. There is no
        // process-wide Tempest discovery state to shut down in this profile.
    }
}
