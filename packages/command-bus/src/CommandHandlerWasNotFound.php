<?php

declare(strict_types=1);

namespace Tempest\CommandBus;

use Exception;

final class CommandHandlerWasNotFound extends Exception
{
    public function __construct(object $command)
    {
        $commandName = get_class($command);

        parent::__construct("No handler found for [{$commandName}].");
    }
}
