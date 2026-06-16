<?php

declare(strict_types=1);

namespace Adanos\Api;

use Adanos\Http\HttpClient;

final class SentimentNamespace extends PlatformNamespace
{
    public function __construct(HttpClient $http)
    {
        parent::__construct($http, '/sentiment/v1');
    }

    /**
     * Analyze one finance or trading text with the direct Finance Sentiment API.
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function analyze(string $text): array
    {
        return $this->post('/analyze', ['text' => $text]);
    }
}
