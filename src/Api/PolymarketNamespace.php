<?php

declare(strict_types=1);

namespace Adanos\Api;

use Adanos\Http\HttpClient;

final class PolymarketNamespace extends PlatformNamespace
{
    public function __construct(HttpClient $http)
    {
        parent::__construct($http, '/polymarket/stocks/v1');
    }

    /**
     * @param array{from?: string, to?: string, days?: int, limit?: int, offset?: int, type?: string} $options
     * @return array<string, mixed>|list<mixed>
     */
    public function trending(array $options = []): array
    {
        return $this->get('/trending', [
            ...$this->periodParams($options),
            'limit' => $options['limit'] ?? null,
            'offset' => $options['offset'] ?? null,
            'type' => $options['type'] ?? null,
        ]);
    }

    /**
     * @param array{from?: string, to?: string, days?: int, limit?: int, offset?: int} $options
     * @return array<string, mixed>|list<mixed>
     */
    public function trendingSectors(array $options = []): array
    {
        return $this->get('/trending/sectors', [
            ...$this->periodParams($options),
            'limit' => $options['limit'] ?? null,
            'offset' => $options['offset'] ?? null,
        ]);
    }

    /**
     * @param array{from?: string, to?: string, days?: int, limit?: int, offset?: int} $options
     * @return array<string, mixed>|list<mixed>
     */
    public function trendingCountries(array $options = []): array
    {
        return $this->get('/trending/countries', [
            ...$this->periodParams($options),
            'limit' => $options['limit'] ?? null,
            'offset' => $options['offset'] ?? null,
        ]);
    }

    /**
     * @param array{from?: string, to?: string, days?: int} $options
     * @return array<string, mixed>|list<mixed>
     */
    public function stock(string $ticker, array $options = []): array
    {
        return $this->get('/stock/' . rawurlencode($ticker), $this->periodParams($options));
    }

    /**
     * @param array{from?: string, to?: string, days?: int, limit?: int, offset?: int} $options
     * @return array<string, mixed>|list<mixed>
     */
    public function mentions(string $ticker, array $options = []): array
    {
        return $this->get('/stock/' . rawurlencode($ticker) . '/mentions', [
            ...$this->periodParams($options),
            'limit' => $options['limit'] ?? null,
            'offset' => $options['offset'] ?? null,
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
     * @param list<string> $tickers
     * @param array{from?: string, to?: string, days?: int} $options
     * @return array<string, mixed>|list<mixed>
     */
    public function compare(array $tickers, array $options = []): array
    {
        return $this->get('/compare', [
            'tickers' => implode(',', $tickers),
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
