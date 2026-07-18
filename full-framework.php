<?php

declare(strict_types=1);

use Tempest\Router\HttpApplication;

// Diagnostic entry point for the unbounded upstream boot path. Unlike the
// finite profile in elephc/, compiling this file loads the root Composer graph.
HttpApplication::boot(__DIR__)->run();
