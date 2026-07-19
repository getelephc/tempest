<?php

declare(strict_types=1);

namespace Symfony\Component\Cache\Adapter;

use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\CacheItem;

class ArrayAdapter implements AdapterInterface
{
    /** @var array<string, CacheItemInterface> */
    private array $items = [];

    public function __construct(
        int $defaultLifetime = 0,
        bool $storeSerialized = true,
        float $maxLifetime = 0,
        int $maxItems = 0,
        mixed $clock = null,
    ) {
    }

    public function getItem(string $key): CacheItemInterface
    {
        return $this->items[$key] ?? new CacheItem($key);
    }

    public function getItems(array $keys = []): iterable
    {
        $items = [];

        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }

        return $items;
    }

    public function hasItem(string $key): bool
    {
        return isset($this->items[$key]);
    }

    public function clear(): bool
    {
        $this->items = [];

        return true;
    }

    public function deleteItem(string $key): bool
    {
        unset($this->items[$key]);

        return true;
    }

    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->deleteItem($key);
        }

        return true;
    }

    public function save(CacheItemInterface $item): bool
    {
        $this->items[$item->getKey()] = $item;

        return true;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->save($item);
    }

    public function commit(): bool
    {
        return true;
    }
}
