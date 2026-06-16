# Changelog

All notable changes to `adanos/adanos-php`.

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
Versioning: [Semantic Versioning](https://semver.org/spec/v2.0.0.html)

## [1.0.0] - 2026-06-16

### Added
- Initial PHP SDK for Adanos Market Sentiment API `1.41.3`.
- Added `AdanosClient` with Reddit Stocks, News Stocks, X/Twitter Stocks,
  Polymarket Stocks, Reddit Crypto, Direct Finance Sentiment, and root health
  namespaces.
- Added `StockSentimentClient` compatibility alias.
- Added `ApiException` with status, detail, decoded payload, and response
  headers.
- Added PHPUnit, PHPStan, PSR-12 linting, GitHub Actions CI, and optional live
  smoke tests.
