<?php

declare(strict_types=1);

namespace Symfony\Component\Process;

final class Process
{
    public const OUT = 'out';
    public const ERR = 'err';

    private bool $running = false;
    private int $exitCode = 0;
    private string $output = '';
    private string $errorOutput = '';

    public function __construct(
        array $command,
        ?string $cwd = null,
        ?array $env = null,
        mixed $input = null,
        ?float $timeout = 60.0,
    ) {
    }

    public static function fromShellCommandline(
        string $command,
        ?string $cwd = null,
        ?array $env = null,
        mixed $input = null,
        ?float $timeout = 60.0,
    ): self {
        return new self([$command], $cwd, $env, $input, $timeout);
    }

    public function start(?callable $callback = null, array $env = []): void
    {
        $this->running = false;
    }

    public function run(?callable $callback = null, array $env = []): int
    {
        $this->start($callback, $env);

        return $this->exitCode;
    }

    public function mustRun(?callable $callback = null, array $env = []): self
    {
        $this->run($callback, $env);

        return $this;
    }

    public function wait(?callable $callback = null): int
    {
        $this->running = false;

        return $this->exitCode;
    }

    public function stop(float $timeout = 10.0, ?int $signal = null): ?int
    {
        $this->running = false;

        return $this->exitCode;
    }

    public function signal(int $signal): void
    {
    }

    public function isRunning(): bool
    {
        return $this->running;
    }

    public function isSuccessful(): bool
    {
        return $this->exitCode === 0;
    }

    public function getExitCode(): ?int
    {
        return $this->exitCode;
    }

    public function getPid(): ?int
    {
        return null;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getErrorOutput(): string
    {
        return $this->errorOutput;
    }

    public function setWorkingDirectory(string $cwd): self
    {
        return $this;
    }

    public function setTimeout(?float $timeout): self
    {
        return $this;
    }

    public function setIdleTimeout(?float $timeout): self
    {
        return $this;
    }

    public function setInput(mixed $input): self
    {
        return $this;
    }

    public function disableOutput(): self
    {
        return $this;
    }

    public function setTty(bool $tty): self
    {
        return $this;
    }

    public function setOptions(array $options): self
    {
        return $this;
    }
}
