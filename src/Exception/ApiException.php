<?php

declare(strict_types=1);

namespace Adanos\Exception;

use Throwable;

class ApiException extends AdanosException
{
    /**
     * @param array<string, mixed>|list<mixed>|null $payload
     * @param array<string, list<string>> $headers
     */
    public function __construct(
        private readonly int $statusCode,
        private readonly string $detail,
        private readonly array|null $payload = null,
        private readonly array $headers = [],
        ?Throwable $previous = null
    ) {
        parent::__construct(sprintf('%d: %s', $statusCode, $detail), $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getDetail(): string
    {
        return $this->detail;
    }

    /**
     * @return array<string, mixed>|list<mixed>|null
     */
    public function getPayload(): array|null
    {
        return $this->payload;
    }

    /**
     * @return array<string, list<string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
