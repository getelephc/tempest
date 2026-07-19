<?php

declare(strict_types=1);

namespace App;

use Tempest\Http\Response;
use Tempest\Http\Responses\Json;
use Tempest\Router\Get;

final class HealthController
{
    #[Get('/health')]
    public function __invoke(): Response
    {
        return new Json([
            'status' => 'ok',
            'framework' => 'tempest',
            'runtime' => 'elephc',
            'pipeline' => 'original',
        ]);
    }
}
