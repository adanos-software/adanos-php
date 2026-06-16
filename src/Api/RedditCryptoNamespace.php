<?php

declare(strict_types=1);

namespace Adanos\Api;

use Adanos\Http\HttpClient;

final class RedditCryptoNamespace extends PlatformNamespace
{
    public function __construct(HttpClient $http)
    {
        parent::__construct($http, '/reddit/crypto/v1');
    }

    /**
     * @param array{from?: string, to?: string, days?: int, limit?: int, offset?: int} $options
     * @return array<string, mixed>|list<mixed>
     */
    public function trending(array $options = []): array
    {
        return $this->get('/trending', [
            ...$this->periodParams($options),
            'limit' => $options['limit'] ?? null,
            'offset' => $options['offset'] ?? null,
        ]);
    }

    /**
     * @param array{from?: string, to?: string, days?: int} $options
     * @return array<string, mixed>|list<mixed>
     */
    public function token(string $symbol, array $options = []): array
    {
        return $this->get('/token/' . rawurlencode($symbol), $this->periodParams($options));
    }

    /**
     * @param array{from?: string, to?: string, days?: int, limit?: int, offset?: int, includeInherited?: bool} $options
     * @return array<string, mixed>|list<mixed>
     */
    public function mentions(string $symbol, array $options = []): array
    {
        return $this->get('/token/' . rawurlencode($symbol) . '/mentions', [
            ...$this->periodParams($options),
            'limit' => $options['limit'] ?? null,
            'offset' => $options['offset'] ?? null,
            'include_inherited' => $options['includeInherited'] ?? null,
        ]);
    }

    /**
     * @param array{limit?: int} $options
     * @return array<string, mixed>|list<mixed>
     */
    public function search(string $query, array $options = []): array
    {
        return $this->get('/search', [
            'q' => $query,
            'limit' => $options['limit'] ?? null,
        ]);
    }

    /**
     * @param list<string> $symbols
     * @param array{from?: string, to?: string, days?: int} $options
     * @return array<string, mixed>|list<mixed>
     */
    public function compare(array $symbols, array $options = []): array
    {
        return $this->get('/compare', [
            'symbols' => implode(',', $symbols),
            ...$this->periodParams($options),
        ]);
    }

    /**
     * @param array{from?: string, to?: string, days?: int} $options
     * @return array<string, mixed>|list<mixed>
     */
    public function marketSentiment(array $options = []): array
    {
        return $this->get('/market-sentiment', $this->periodParams($options));
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function stats(): array
    {
        return $this->get('/stats');
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function health(): array
    {
        return $this->get('/health');
    }
}
