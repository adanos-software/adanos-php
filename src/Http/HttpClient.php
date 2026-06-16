<?php

declare(strict_types=1);

namespace Adanos\Http;

use Adanos\Exception\AdanosException;
use Adanos\Exception\ApiException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

final class HttpClient
{
    private readonly string $baseUrl;
    private readonly ClientInterface $client;

    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://api.adanos.org',
        float $timeout = 30.0,
        ?ClientInterface $client = null
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->client = $client ?? new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $timeout,
        ]);

        $this->apiKey = $apiKey;
    }

    private readonly string $apiKey;

    /**
     * @param array<string, scalar|null> $query
     * @return array<string, mixed>|list<mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, $query);
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>|list<mixed>
     */
    public function post(string $path, array $body): array
    {
        return $this->request('POST', $path, [], $body);
    }

    /**
     * @param array<string, scalar|null> $query
     * @param array<string, mixed>|null $body
     * @return array<string, mixed>|list<mixed>
     */
    private function request(string $method, string $path, array $query = [], ?array $body = null): array
    {
        try {
            $response = $this->client->request($method, $this->baseUrl . $path, [
                'headers' => [
                    'Accept' => 'application/json',
                    'X-API-Key' => $this->apiKey,
                ],
                'http_errors' => false,
                'query' => $this->filterQuery($query),
                'json' => $body,
            ]);
        } catch (GuzzleException $exception) {
            throw new AdanosException($exception->getMessage(), previous: $exception);
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            $payload = $this->decodeResponse($response, strict: false);

            throw new ApiException(
                $statusCode,
                $this->formatErrorDetail($payload, $response->getReasonPhrase()),
                $payload,
                $response->getHeaders()
            );
        }

        $payload = $this->decodeResponse($response, strict: true);

        return $payload ?? [];
    }

    /**
     * @param array<string, scalar|null> $query
     * @return array<string, scalar>
     */
    private function filterQuery(array $query): array
    {
        $filtered = [];

        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }

            $filtered[$key] = is_bool($value) ? ($value ? 'true' : 'false') : $value;
        }

        return $filtered;
    }

    /**
     * @return array<string, mixed>|list<mixed>|null
     */
    private function decodeResponse(ResponseInterface $response, bool $strict): array|null
    {
        $body = (string) $response->getBody();

        if ($body === '') {
            return null;
        }

        try {
            $decoded = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            if (!$strict) {
                return null;
            }

            throw new AdanosException('API returned invalid JSON.', previous: $exception);
        }

        if (!is_array($decoded)) {
            if (!$strict) {
                return null;
            }

            throw new AdanosException('API returned an unsupported JSON payload.');
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed>|list<mixed>|null $payload
     */
    private function formatErrorDetail(array|null $payload, string $fallback): string
    {
        if ($payload === null || !array_key_exists('detail', $payload)) {
            return $fallback !== '' ? $fallback : 'HTTP error';
        }

        return $this->stringifyDetail($payload['detail']);
    }

    private function stringifyDetail(mixed $detail): string
    {
        if (is_string($detail) && trim($detail) !== '') {
            return trim($detail);
        }

        if (is_array($detail)) {
            foreach (['message', 'error', 'detail'] as $key) {
                if (isset($detail[$key]) && is_string($detail[$key]) && trim($detail[$key]) !== '') {
                    return trim($detail[$key]);
                }
            }

            try {
                return json_encode($detail, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                return 'HTTP error';
            }
        }

        return 'HTTP error';
    }
}
