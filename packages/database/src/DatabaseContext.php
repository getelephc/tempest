<?php

namespace Tempest\Database;

use Tempest\Database\Config\DatabaseDialect;
use Tempest\Mapper\Context;

final class DatabaseContext implements Context
{
    public string $name = self::class;

    public function __construct(
        public DatabaseDialect $dialect,
    ) {}
}
