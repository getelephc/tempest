<?php

declare(strict_types=1);

namespace Symfony\Component\Cache\Adapter;

class FilesystemAdapter extends ArrayAdapter
{
    public function __construct(
        string $namespace = '',
        int $defaultLifetime = 0,
        ?string $directory = null,
        mixed $marshaller = null,
    ) {
        parent::__construct($defaultLifetime);
    }
}
