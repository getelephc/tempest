<?php

declare(strict_types=1);

namespace Whoops;

final class Run
{
    public function pushHandler(mixed $handler): self
    {
        return $this;
    }

    public function register(): void
    {
    }
}
