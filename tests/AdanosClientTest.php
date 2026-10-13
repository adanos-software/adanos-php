<?php

declare(strict_types=1);

namespace Adanos\Tests;

use Adanos\AdanosClient;
use Adanos\Exception\ApiException;
use Adanos\Http\HttpClient;
use Adanos\StockSentimentClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class AdanosClientTest extends TestCase
{
    /**
     * @var list<array{request: RequestInterface}>
     */
    private array $history = [];

    private function clientWithResponses(Response ...$responses): AdanosClient
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($this->history));

        return new AdanosClient(
            apiKey: 'sdk_test_key',
            httpClient: new Client(['handler' => $stack])
        );
    }

    public function testRedditTrendingBuildsExpectedRequest(): void
    {
        $client = $this->clientWithResponses(new Response(200, [], '[{"ticker":"TSLA"}]'));

        $result = $client->reddit->trending([
            'from' => '2026-06-01',
            'to' => '2026-06-07',
            'limit' => 10,
            'type' => 'stock',
        ]);

        self::assertSame('TSLA', $result[0]['ticker']);

        $request = $this->history[0]['request'];
        self::assertSame('GET', $request->getMethod());
        self::assertSame('/reddit/stocks/v1/trending', $request->getUri()->getPath());
        self::assertSame('sdk_test_key', $request->getHeaderLine('X-API-Key'));

        parse_str($request->getUri()->getQuery(), $query);
        self::assertSame('2026-06-01', $query['from']);
        self::assertSame('2026-06-07', $query['to']);
        self::assertSame('10', $query['limit']);
        self::assertSame('stock', $query['type']);
        self::assertArrayNotHasKey('days', $query);
    }

    public function testRedditMentionsSerializesInheritedFlag(): void
    {
        $client = $this->clientWithResponses(new Response(200, [], '{"mentions":[]}'));

        $client->reddit->mentions('TSLA', [
            'limit' => 5,
            'offset' => 10,
            'includeInherited' => true,
        ]);

        $request = $this->history[0]['request'];
        self::assertSame('/reddit/stocks/v1/stock/TSLA/mentions', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        self::assertSame('true', $query['include_inherited']);
        self::assertSame('5', $query['limit']);
        self::assertSame('10', $query['offset']);
    }

    public function testCompareUsesPlatformSpecificParameterNames(): void
    {
        $client = $this->clientWithResponses(
            new Response(200, [], '{"stocks":[]}'),
            new Response(200, [], '{"tokens":[]}')
        );

        $client->polymarket->compare(['AAPL', 'TSLA']);
        $client->crypto->compare(['BTC', 'ETH']);

        parse_str($this->history[0]['request']->getUri()->getQuery(), $stocksQuery);
        parse_str($this->history[1]['request']->getUri()->getQuery(), $cryptoQuery);

        self::assertSame('/polymarket/stocks/v1/compare', $this->history[0]['request']->getUri()->getPath());
        self::assertSame('AAPL,TSLA', $stocksQuery['tickers']);

        self::assertSame('/reddit/crypto/v1/compare', $this->history[1]['request']->getUri()->getPath());
        self::assertSame('BTC,ETH', $cryptoQuery['symbols']);
    }

    public function testSentimentAnalyzePostsJsonBody(): void
    {
        $client = $this->clientWithResponses(new Response(
            200,
            [],
            '{"sentiment_label":"positive","components":{"engine_version":"5.5","model_score":null}}'
        ));

        $result = $client->sentiment->analyze('TSLA looks like a short squeeze setup');

        self::assertSame('positive', $result['sentiment_label']);
        self::assertNull($result['components']['model_score']);

        $request = $this->history[0]['request'];
        self::assertSame('POST', $request->getMethod());
        self::assertSame('/sentiment/v1/analyze', $request->getUri()->getPath());
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
        self::assertSame(
            ['text' => 'TSLA looks like a short squeeze setup'],
            json_decode((string) $request->getBody(), true)
        );
    }

    public function testSentimentAnalyzePostsTargetAndPreservesModelScore(): void
    {
        $client = $this->clientWithResponses(new Response(
            200,
            [],
            '{"sentiment_label":"negative","components":{"engine_version":"5.5","model_score":-0.8}}'
        ));

        $result = $client->sentiment->analyze('Goldman Sachs cuts Apple price target', target: 'Apple');

        self::assertSame('negative', $result['sentiment_label']);
        self::assertSame(-0.8, $result['components']['model_score']);
        self::assertSame(
            ['text' => 'Goldman Sachs cuts Apple price target', 'target' => 'Apple'],
            json_decode((string) $this->history[0]['request']->getBody(), true)
        );
    }

    public function testDefaultAndExplicitBaseUrls(): void
    {
        foreach ([null, 'https://api.adanos.org/', 'https://proxy.example.test/'] as $baseUrl) {
            $stack = HandlerStack::create(new MockHandler([new Response(200, [], '{"status":"ok"}')]));
            $stack->push(Middleware::history($this->history));
            $http = new Client(['handler' => $stack]);
            $client = $baseUrl === null
                ? new AdanosClient(apiKey: 'sdk_test_key', httpClient: $http)
                : new AdanosClient(apiKey: 'sdk_test_key', baseUrl: $baseUrl, httpClient: $http);

            self::assertSame(['status' => 'ok'], $client->health());
            self::assertSame(
                ($baseUrl === null ? 'https://api.adanos.com' : rtrim($baseUrl, '/')) . '/health',
                (string) $this->history[array_key_last($this->history)]['request']->getUri()
            );
        }

        $stack = HandlerStack::create(new MockHandler([new Response(200, [], '{}')]));
        $stack->push(Middleware::history($this->history));
        $http = new HttpClient(apiKey: 'sdk_test_key', client: new Client(['handler' => $stack]));
        $http->get('/health');
        self::assertSame('https://api.adanos.com/health', (string) $this->history[3]['request']->getUri());
    }

    public function testNewsAcceptsExchangeQualifiedTickers(): void
    {
        $client = $this->clientWithResponses(
            new Response(200, [], '{"ticker":"VOD.L"}'),
            new Response(200, [], '{"results":[]}'),
            new Response(200, [], '{"ticker":"VOD.L"}'),
            new Response(200, [], '{"stocks":[]}')
        );

        self::assertSame('VOD.L', $client->news->stock('VOD.L')['ticker']);
        $client->news->mentions('VOD.L', ['limit' => 1]);
        $client->news->explain('VOD.L');
        $client->news->compare(['VOD.L', 'AAPL']);

        self::assertSame('/news/stocks/v1/stock/VOD.L', $this->history[0]['request']->getUri()->getPath());
        self::assertSame('/news/stocks/v1/stock/VOD.L/mentions', $this->history[1]['request']->getUri()->getPath());
        self::assertSame('/news/stocks/v1/stock/VOD.L/explain', $this->history[2]['request']->getUri()->getPath());
        parse_str($this->history[3]['request']->getUri()->getQuery(), $query);
        self::assertSame('VOD.L,AAPL', $query['tickers']);
    }

    public function testCryptoMentionsPreservesSignedScoresAndUtcTimestamps(): void
    {
        $client = $this->clientWithResponses(new Response(
            200,
            [],
            '{"results":[{"upvotes":-3,"created_utc":"2026-10-13T10:00:00Z","sentiment_score":null}]}'
        ));

        $result = $client->crypto->mentions('BTC', ['limit' => 1]);

        self::assertSame(-3, $result['results'][0]['upvotes']);
        self::assertSame('2026-10-13T10:00:00Z', $result['results'][0]['created_utc']);
        self::assertNull($result['results'][0]['sentiment_score']);
    }

    public function testPolymarketStatsUsesApi144Counters(): void
    {
        $client = $this->clientWithResponses(new Response(
            200,
            [],
            json_encode([
                'total_trades' => 15420,
                'total_markets' => 713,
                'unique_tickers' => 119,
                'open_markets_current' => 284,
                'open_tickers_current' => 71,
                'traded_markets_today' => 39,
                'traded_tickers_today' => 17,
                'trades_today' => 84,
                'supported_tickers' => 11800,
            ], JSON_THROW_ON_ERROR)
        ));

        $result = $client->polymarket->stats();

        self::assertSame('/polymarket/stocks/v1/stats', $this->history[0]['request']->getUri()->getPath());
        self::assertSame(284, $result['open_markets_current']);
        self::assertSame(17, $result['traded_tickers_today']);
    }

    public function testPolymarketStockExposesApi149PulseAndDailyPercentages(): void
    {
        $client = $this->clientWithResponses(new Response(
            200,
            [],
            json_encode([
                'ticker' => 'AAPL',
                'found' => true,
                'unique_traders' => null,
                'daily_trend' => [[
                    'date' => '2026-08-08',
                    'trade_count' => 5,
                    'bullish_pct' => 64,
                    'bearish_pct' => 21,
                ]],
                'pulse' => [
                    'mood' => 'mixed',
                    'confidence' => 60.0,
                    'thin_data' => false,
                    'why' => ['opposing_market_signals'],
                    'warnings' => ['high_average_spread'],
                    'evidence' => ['directional_coverage' => 1.0],
                ],
            ], JSON_THROW_ON_ERROR)
        ));

        $result = $client->polymarket->stock('AAPL');

        self::assertSame('mixed', $result['pulse']['mood']);
        self::assertSame(64, $result['daily_trend'][0]['bullish_pct']);
        self::assertNull($result['unique_traders']);
    }

    public function testCompareLimitErrorUsesStructuredApi149Message(): void
    {
        $client = $this->clientWithResponses(new Response(
            400,
            [],
            '{"detail":{"error":"too_many_tickers","message":"Too many tickers.","max_items":10,"item_name":"ticker"}}'
        ));

        try {
            $client->reddit->compare(array_fill(0, 11, 'AAPL'));
            self::fail('Expected ApiException.');
        } catch (ApiException $exception) {
            self::assertSame(400, $exception->getStatusCode());
            self::assertSame('Too many tickers.', $exception->getDetail());
            self::assertSame(10, $exception->getPayload()['detail']['max_items']);
        }
    }

    public function testApiExceptionIncludesStatusDetailPayloadAndHeaders(): void
    {
        $client = $this->clientWithResponses(new Response(
            403,
            ['X-Request-ID' => 'req_test'],
            '{"detail":{"error":"Professional account required","message":"Upgrade required"}}'
        ));

        try {
            $client->sentiment->analyze('TSLA');
            self::fail('Expected ApiException.');
        } catch (ApiException $exception) {
            self::assertSame(403, $exception->getStatusCode());
            self::assertSame('Upgrade required', $exception->getDetail());
            self::assertSame('Professional account required', $exception->getPayload()['detail']['error']);
            self::assertSame(['req_test'], $exception->getHeaders()['X-Request-ID']);
        }
    }

    public function testApiExceptionPreservesNonJsonErrorStatusAndHeaders(): void
    {
        $client = $this->clientWithResponses(new Response(
            502,
            ['X-Request-ID' => 'req_proxy'],
            '<html>bad gateway</html>'
        ));

        try {
            $client->reddit->trending();
            self::fail('Expected ApiException.');
        } catch (ApiException $exception) {
            self::assertSame(502, $exception->getStatusCode());
            self::assertSame('Bad Gateway', $exception->getDetail());
            self::assertNull($exception->getPayload());
            self::assertSame(['req_proxy'], $exception->getHeaders()['X-Request-ID']);
        }
    }

    public function testCompatibilityClientExtendsPrimaryClient(): void
    {
        $client = new StockSentimentClient(
            apiKey: 'sdk_test_key',
            httpClient: new Client(['handler' => HandlerStack::create(new MockHandler())])
        );

        self::assertInstanceOf(AdanosClient::class, $client);
    }
}
