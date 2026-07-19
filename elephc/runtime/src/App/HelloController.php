<?php

declare(strict_types=1);

namespace App;

use Tempest\Http\Response;
use Tempest\Http\Responses\Ok;
use Tempest\Router\Get;

final class HelloController
{
    #[Get('/hello/{name}')]
    public function __invoke(string $name): Response
    {
        return new Ok("Hello, {$name}!\n");
    }
}
