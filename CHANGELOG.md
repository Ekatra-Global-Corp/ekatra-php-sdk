# Changelog

## 2.2.0 (unreleased, currently 2.2.0-RC1)

### Added
- Support for **Laravel 12 and 13**.
- Support for **PHP 8.5**.

### Changed
- Minimum PHP is now formally **7.4**. The SDK already required 7.4 (typed properties) and crashed on 7.2/7.3; the constraint now says so.
- `/ekatra/test/*` routes are **off by default**. Enable them with `EKATRA_TEST_ROUTES_ENABLED=true` (still limited to `local`/`testing` environments).
- Removed unused dependencies `guzzlehttp/guzzle` and `illuminate/validation`. Laravel apps keep their own Guzzle; nothing in the SDK used it.

### Fixed
- `config/ekatra.php` was never loaded or publishable because of a wrong path. `php artisan vendor:publish --tag=ekatra-config` now works.
- The `Ekatra` facade is now bound in the container, so all `EkatraSDK` methods work through it (e.g. `Ekatra::transformProduct()`).
- Media MIME lookup used a 0.5s connect timeout that PHP silently cast to 0 (no limit). It now uses millisecond timeouts within the existing 1-second budget.
- User-Agent for media lookups reported a hard-coded `2.1.4`; it now reports the real SDK version.

### Security
- Media MIME lookup (used only for URLs without a recognisable file extension) no longer connects to private, loopback, link-local or reserved IP addresses, including via redirects or DNS rebinding. Public media URLs behave exactly as before.

### Unchanged
- Product and variant field mapping, discount/`discountLabel` handling, size linking, variations and the response structure. This is verified by mapping snapshot tests recorded from v2.1.9 that run on every PHP and Laravel version in CI.
