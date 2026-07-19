<?php

declare(strict_types=1);

namespace Symfony\Component\VarDumper;

final class VarDumper
{
    public static function dump(mixed $value, ?string $label = null): mixed
    {
        if ($label !== null) {
            echo $label . ': ';
        }

        echo var_export($value, true);

        return $value;
    }

    public static function setHandler(?callable $handler): ?callable
    {
        return null;
    }
}
