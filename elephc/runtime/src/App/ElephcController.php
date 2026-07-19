<?php

declare(strict_types=1);

namespace App;

use Tempest\Http\Response;
use Tempest\Http\Responses\Redirect;
use Tempest\Router\Get;

final class ElephcController
{
    #[Get('/elephc')]
    public function __invoke(): Response
    {
        return new Redirect('https://elephc.dev');
    }
}
