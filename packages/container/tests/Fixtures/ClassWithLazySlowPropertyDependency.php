<?php
declare(strict_types=1);

namespace Tempest\Container\Tests\Fixtures;

use Tempest\Container\Inject;
use Tempest\Container\Proxy;

final class ClassWithLazySlowPropertyDependency
{
    #[Inject]
    #[Proxy]
    public SlowDependency $dependency;

    public function __construct() {}
}
