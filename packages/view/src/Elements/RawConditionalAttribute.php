<?php

declare(strict_types=1);

namespace Tempest\View\Elements;

final readonly class RawConditionalAttribute
{
    public function __construct(
        public string $name,
        public string $value,
    ) {}
}
