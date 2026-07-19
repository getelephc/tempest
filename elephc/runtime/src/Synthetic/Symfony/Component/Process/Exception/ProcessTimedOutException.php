<?php

declare(strict_types=1);

namespace Symfony\Component\Process\Exception;

use RuntimeException;
use Symfony\Component\Process\Process;

final class ProcessTimedOutException extends RuntimeException
{
    public const TYPE_GENERAL = 1;
    public const TYPE_IDLE = 2;

    public function __construct(Process $process, int $timeoutType = self::TYPE_GENERAL)
    {
        parent::__construct('The synthetic Elephc process adapter does not execute external processes.');
    }
}
