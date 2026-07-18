<?php

namespace Tempest\Core\Exceptions;

use Throwable;

final class TestingExceptionProcessor implements ExceptionProcessor
{
    /**
     * @var array<Throwable> List of processed exceptions.
     */
    public array $processed = [];

    public function __construct(
        public ExceptionProcessor $processor,
        public bool $enabled,
    ) {}

    public function process(Throwable $throwable): void
    {
        $this->processed[] = $throwable;

        if ($this->enabled === false) {
            return;
        }

        $this->processor->process($throwable);
    }
}
