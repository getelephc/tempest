<?php

namespace Tempest\Log\Config;

use Tempest\Log\LogConfig;
use UnitEnum;

final class NullLogConfig implements LogConfig
{
    public array $logChannels {
        get => [];
    }

    /**
     * A logging configuration that does not log anything.
     */
    public function __construct(
        public ?string $prefix = null,
        public UnitEnum|string|null $tag = null,
    ) {}
}
