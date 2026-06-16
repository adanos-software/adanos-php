<?php

declare(strict_types=1);

namespace Adanos\Api;

use Adanos\Http\HttpClient;

abstract class PlatformNamespace
{
    public function __construct(
        protected readonly HttpClient $http,
        protected readonly string $prefix
    ) {
    }

    /**
     * @param array<string, scalar|null> $query
     * @return array<string, mixed>|list<mixed>
     */
    protected function get(string $path, array $query = []): array
    {
        return $this->http->get($this->prefix . $path, $query);
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>|list<mixed>
     */
    protected function post(string $path, array $body): array
    {
        return $this->http->post($this->prefix . $path, $body);
    }

    /**
     * @param array<string, mixed> $options
     * @return array{from?: string, to?: string, days?: int}
     */
    protected function periodParams(array $options): array
    {
        return [
            'from' => $options['from'] ?? null,
            'to' => $options['to'] ?? null,
            'days' => $options['days'] ?? null,
        ];
    }
}
