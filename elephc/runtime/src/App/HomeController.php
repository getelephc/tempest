<?php

declare(strict_types=1);

namespace App;

use Tempest\Http\Response;
use Tempest\Http\Responses\Ok;
use Tempest\Router\Get;

final class HomeController
{
    #[Get('/')]
    public function __invoke(): Response
    {
        return new Ok(<<<'HTML'
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Tempest on Elephc</title></head>
<body><h1>Tempest on Elephc</h1><p>Served by the original Tempest HTTP pipeline.</p></body>
</html>
HTML)
            ->addHeader('Content-Type', 'text/html; charset=utf-8')
            ->addHeader('X-Powered-By', 'Tempest-on-Elephc');
    }
}
