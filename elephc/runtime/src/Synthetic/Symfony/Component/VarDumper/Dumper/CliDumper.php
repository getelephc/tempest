<?php

declare(strict_types=1);

namespace Symfony\Component\VarDumper\Dumper;

final class CliDumper
{
    private mixed $output;

    public function __construct(mixed $output = null)
    {
        $this->output = $output;
    }

    public function setColors(bool $colors): void
    {
    }

    public function dump(mixed $value): void
    {
        $line = var_export($value, true);

        if (is_callable($this->output)) {
            $output = $this->output;
            $output($line, 0);

            return;
        }

        echo $line;
    }
}
