<?php

declare(strict_types=1);

namespace Encodia\Health\Checks;

final class CheckResultDto
{
    public function __construct(
        public bool $ok,
        /** @var array<string,mixed> */
        public array $meta,
        public string $summary,
        public string $message
    ) {
        //
    }

    public static function ok(): self
    {
        return new self(
            ok: true,
            meta: [],
            summary: '',
            message: ''
        );
    }

    /**
     * @param  array<string,mixed>  $meta
     */
    public static function error(array $meta, string $summary, string $message): self
    {
        return new self(
            ok: false,
            meta: $meta,
            summary: $summary,
            message: $message
        );
    }

    public function hasFailed(): bool
    {
        return ! $this->ok;
    }
}
