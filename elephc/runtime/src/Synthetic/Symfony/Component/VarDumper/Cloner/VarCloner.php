<?php

declare(strict_types=1);

namespace Symfony\Component\VarDumper\Cloner;

final class VarCloner
{
    public function cloneVar(mixed $value, int $filter = 0): mixed
    {
        return $value;
    }
}
