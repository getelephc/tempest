<?php

declare(strict_types=1);

namespace Symfony\Component\Cache\Adapter;

final class RedisAdapter extends ArrayAdapter
{
    public function __construct(
        mixed $redis,
        string $namespace = '',
        int $defaultLifetime = 0,
        mixed $marshaller = null,
    ) {
        parent::__construct($defaultLifetime);
    }
}
