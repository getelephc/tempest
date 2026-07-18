<?php

namespace Tempest\Router\Exceptions;

use Exception;
use Tempest\Reflection\MethodReflector;
use Tempest\Reflection\ParameterReflector;

final class EnumRouteValueWasInvalid extends Exception
{
    public function __construct(
        public readonly MethodReflector $handler,
        public readonly ParameterReflector $parameter,
    ) {}
}
