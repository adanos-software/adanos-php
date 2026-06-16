<?php

declare(strict_types=1);

namespace Adanos\Tests;

use Adanos\AdanosClient;
use Adanos\Exception\ApiException;
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
        $client = $this->clientWithResponses(new Response(200, [], '{"sentiment_label":"positive"}'));

        $result = $client->sentiment->analyze('TSLA looks like a short squeeze setup');

        self::assertSame('positive', $result['sentiment_label']);

        $request = $this->history[0]['request'];
        self::assertSame('POST', $request->getMethod());
        self::assertSame('/sentiment/v1/analyze', $request->getUri()->getPath());
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
        self::assertSame(
            ['text' => 'TSLA looks like a short squeeze setup'],
            json_decode((string) $request->getBody(), true)
        );
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
