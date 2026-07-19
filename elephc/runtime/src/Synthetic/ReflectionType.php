<?php

declare(strict_types=1);

/** AOT type declaration for PHP's internal ReflectionType class. */
abstract class ReflectionType implements Stringable
{
    abstract public function allowsNull(): bool;

    abstract public function __toString(): string;
}
