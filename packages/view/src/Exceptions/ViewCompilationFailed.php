<?php

declare(strict_types=1);

namespace Tempest\View\Exceptions;

use Exception;
use Tempest\Core\ProvidesContext;
use Throwable;

final class ViewCompilationFailed extends Exception implements ProvidesContext
{
    public function __construct(
        public readonly string $path,
        public readonly string $content,
        Throwable $previous,
        public readonly ?string $sourcePath = null,
        public readonly ?int $sourceLine = null,
    ) {
        parent::__construct(
            message: $previous->getMessage(),
            previous: $previous,
        );

        $this->file = $this->sourcePath ?? $this->file;
        $this->line = $this->sourceLine ?? $this->line;
    }

    public function context(): array
    {
        return [
            'path' => $this->path,
            'sourcePath' => $this->sourcePath,
            'sourceLine' => $this->sourceLine,
        ];
    }
}
