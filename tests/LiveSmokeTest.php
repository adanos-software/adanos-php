<?php

declare(strict_types=1);

namespace Adanos\Tests;

use Adanos\AdanosClient;
use PHPUnit\Framework\TestCase;

final class LiveSmokeTest extends TestCase
{
    public function testLiveRedditTrending(): void
    {
        $apiKey = getenv('ADANOS_API_KEY');

        if ($apiKey === false || $apiKey === '') {
            self::markTestSkipped('Set ADANOS_API_KEY to run the live API smoke test.');
        }

        $client = new AdanosClient(apiKey: $apiKey, timeout: 20.0);
        $result = $client->reddit->trending(['limit' => 1]);

        self::assertIsArray($result);
        self::assertArrayHasKey('ticker', $result[0]);
    }

    public function testLiveSentimentAnalyzeWhenEnabled(): void
    {
        $apiKey = getenv('ADANOS_API_KEY');

        if ($apiKey === false || $apiKey === '') {
            self::markTestSkipped('Set ADANOS_API_KEY to run the live sentiment smoke test.');
        }

        if (getenv('ADANOS_LIVE_SENTIMENT') !== '1') {
            self::markTestSkipped('Set ADANOS_LIVE_SENTIMENT=1 to run the Professional-only sentiment smoke test.');
        }

        $client = new AdanosClient(apiKey: $apiKey, timeout: 20.0);
        $result = $client->sentiment->analyze('TSLA looks like a short squeeze setup');

        self::assertSame('TSLA looks like a short squeeze setup', $result['text']);
        self::assertArrayHasKey('sentiment_score', $result);
        self::assertArrayHasKey('sentiment_label', $result);
    }
}
