# adanos/adanos-php

PHP SDK for the [Adanos Market Sentiment API](https://api.adanos.org/docs).

Target API version: `1.41.3`.

## Install

```bash
composer require adanos/adanos-php
```

## Quick Start

```php
<?php

use Adanos\AdanosClient;

$client = new AdanosClient(apiKey: 'sk_live_...');

$trending = $client->reddit->trending(['limit' => 10]);
$tsla = $client->reddit->stock('TSLA');
$health = $client->health();

echo $trending[0]['ticker'] . PHP_EOL;
echo $tsla['buzz_score'] . PHP_EOL;
echo $health['status'] . PHP_EOL;
```

## Namespaces

- `$client->reddit` for Reddit Stocks at `/reddit/stocks/v1`
- `$client->news` for News Stocks at `/news/stocks/v1`
- `$client->x` for X/Twitter Stocks at `/x/stocks/v1`
- `$client->polymarket` for Polymarket Stocks at `/polymarket/stocks/v1`
- `$client->crypto` for Reddit Crypto at `/reddit/crypto/v1`
- `$client->redditCrypto` as an alias for `$client->crypto`
- `$client->sentiment` for direct Finance Sentiment at `/sentiment/v1`
- `$client->health()` for aggregate API health

## Examples

### Reddit Stocks

```php
$trending = $client->reddit->trending(['limit' => 10]);
$sectors = $client->reddit->trendingSectors(['limit' => 10]);
$countries = $client->reddit->trendingCountries(['limit' => 10]);
$tsla = $client->reddit->stock('TSLA', ['from' => '2026-06-01', 'to' => '2026-06-07']);
$mentions = $client->reddit->mentions('TSLA', ['limit' => 10, 'includeInherited' => true]);
$explanation = $client->reddit->explain('TSLA');
$results = $client->reddit->search('Tesla', ['limit' => 10]);
$comparison = $client->reddit->compare(['TSLA', 'AAPL', 'MSFT']);
$market = $client->reddit->marketSentiment();
$stats = $client->reddit->stats();
$health = $client->reddit->health();
```

### News

```php
$trending = $client->news->trending(['source' => 'reuters']);
$sectors = $client->news->trendingSectors(['source' => 'reuters']);
$countries = $client->news->trendingCountries(['source' => 'reuters']);
$nvda = $client->news->stock('NVDA');
$explanation = $client->news->explain('NVDA');
$results = $client->news->search('Nvidia', ['limit' => 10]);
$comparison = $client->news->compare(['NVDA', 'AAPL']);
$market = $client->news->marketSentiment();
```

### X/Twitter

```php
$trending = $client->x->trending(['limit' => 20]);
$nvda = $client->x->stock('NVDA');
$mentions = $client->x->mentions('NVDA', ['limit' => 10]);
$comparison = $client->x->compare(['NVDA', 'AMD']);
$market = $client->x->marketSentiment();
```

### Polymarket

```php
$trending = $client->polymarket->trending(['limit' => 20, 'type' => 'stock']);
$aapl = $client->polymarket->stock('AAPL');
$results = $client->polymarket->search('Apple', ['limit' => 10]);
$comparison = $client->polymarket->compare(['AAPL', 'TSLA']);
$market = $client->polymarket->marketSentiment();
```

### Reddit Crypto

```php
$trending = $client->crypto->trending(['limit' => 20]);
$btc = $client->crypto->token('BTC');
$mentions = $client->crypto->mentions('BTC', [
    'from' => '2026-06-01',
    'to' => '2026-06-07',
    'limit' => 10,
    'includeInherited' => true,
]);
$results = $client->crypto->search('bitcoin', ['limit' => 10]);
$comparison = $client->crypto->compare(['BTC', 'ETH']);
$market = $client->crypto->marketSentiment();
```

### Direct Finance Sentiment

`/sentiment/v1/analyze` requires a Professional account.

```php
$analysis = $client->sentiment->analyze('TSLA looks like a short squeeze setup');

echo $analysis['sentiment_label'] . PHP_EOL;
echo $analysis['sentiment_score'] . PHP_EOL;
```

## Period Options

Use `from` and `to` as inclusive UTC dates for reproducible windows:

```php
$client->reddit->trending([
    'from' => '2026-06-01',
    'to' => '2026-06-07',
    'limit' => 10,
]);
```

`days` remains available as a legacy v1-compatible shorthand. Do not send
`from`, `to`, and `days` together.

## Configuration

```php
$client = new AdanosClient(
    apiKey: 'sk_live_...',
    baseUrl: 'https://api.adanos.org',
    timeout: 60.0,
);
```

## Errors

Non-2xx responses throw `Adanos\Exception\ApiException`.

```php
use Adanos\Exception\ApiException;

try {
    $client->sentiment->analyze('TSLA');
} catch (ApiException $exception) {
    echo $exception->getStatusCode();
    echo $exception->getDetail();
    $payload = $exception->getPayload();
    $headers = $exception->getHeaders();
}
```

## Compatibility Alias

`StockSentimentClient` extends `AdanosClient` for compatibility with older SDK
naming.

```php
use Adanos\StockSentimentClient;

$client = new StockSentimentClient(apiKey: 'sk_live_...');
```

## Development

```bash
composer install
composer check
```

Live smoke tests are skipped unless `ADANOS_API_KEY` is present:

```bash
ADANOS_API_KEY=sk_live_... vendor/bin/phpunit --filter LiveSmokeTest
```

The Professional-only direct sentiment smoke test also requires:

```bash
ADANOS_API_KEY=sk_live_... ADANOS_LIVE_SENTIMENT=1 vendor/bin/phpunit --filter LiveSmokeTest
```

## Release

Publish source from `adanos-software/adanos-php` and connect the repository to
Packagist as `adanos/adanos-php`.

## License

MIT
