<?php

namespace Tempest\Support\NamespaceUtils;

final readonly class Psr4Namespace
{
    public function __construct(
        public string $namespace,
        public string $path,
    ) {}
}
