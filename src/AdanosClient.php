<?php

declare(strict_types=1);

namespace Adanos;

use Adanos\Api\NewsNamespace;
use Adanos\Api\PolymarketNamespace;
use Adanos\Api\RedditCryptoNamespace;
use Adanos\Api\RedditNamespace;
use Adanos\Api\SentimentNamespace;
use Adanos\Api\XNamespace;
use Adanos\Http\HttpClient;
use GuzzleHttp\ClientInterface;

class AdanosClient
{
    public readonly NewsNamespace $news;
    public readonly RedditNamespace $reddit;
    public readonly RedditCryptoNamespace $crypto;
    public readonly RedditCryptoNamespace $redditCrypto;
    public readonly XNamespace $x;
    public readonly PolymarketNamespace $polymarket;
    public readonly SentimentNamespace $sentiment;

    private readonly HttpClient $http;

    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://api.adanos.org',
        float $timeout = 30.0,
        ?ClientInterface $httpClient = null
    ) {
        $this->http = new HttpClient($apiKey, $baseUrl, $timeout, $httpClient);

        $this->news = new NewsNamespace($this->http);
        $this->reddit = new RedditNamespace($this->http);
        $this->crypto = new RedditCryptoNamespace($this->http);
        $this->redditCrypto = $this->crypto;
        $this->x = new XNamespace($this->http);
        $this->polymarket = new PolymarketNamespace($this->http);
        $this->sentiment = new SentimentNamespace($this->http);
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function health(): array
    {
        return $this->http->get('/health');
    }
}
