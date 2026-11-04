# Review Standards

These are the standards the automated reviewer (`.github/workflows/code-review.yml`) applies, and what a person reviewing a pull request should check. CI already enforces style (Pint, PHPCS), types (PHPStan with Larastan at level 10), syntax and workflow syntax, so review the behavior.

## What this package does

It wraps the Microsoft Power BI REST API for Laravel apps. Saloon connectors handle OAuth2 for three account types (service principal, admin service principal, Azure user) across four Microsoft clouds. Each endpoint is a request class that maps its response to a readonly DTO.

## Blocking issues

Ask for changes when the diff does one of these, and name the input or state that goes wrong.

1. **Exposes a credential or tenant data.** A client secret, access token or embed token reaches a log, an exception message or a cache entry that another identity can read. Committed fixtures must not contain real tokens, tenant IDs or report URLs; `PowerBIFixture` redacts them, and a new sensitive field needs an entry there.
2. **Serves a cached response to the wrong identity.** Connectors cache responses in the app's default store. A change to caching must keep one account, tenant or cloud from reading a response cached for another.
3. **Bypasses the account type restrictions.** Power BI rejects some endpoints for some account types. A request limited to certain types must use `HasAccountTypeRestrictions` and test each account type. Sending through a path that skips `PowerBIConnectorBase::send()` skips the check.
4. **Hard-codes a commercial cloud host.** Every API, authority and resource URL comes from the `CloudEnvironment` enum. A literal `api.powerbi.com` or `login.microsoftonline.com` sends GCC, GCC High or DoD traffic to the wrong cloud.
5. **Maps a response incorrectly.** A DTO field that the API can omit must be nullable and tested with the field missing, because a required field throws on a real response. Check field names and nesting against the fixture and the Microsoft documentation.
6. **Keeps state in a long-lived worker.** `PowerBI::$connector` is static, so under Octane it survives between requests. A change must not let one request's authenticated connector or per-request value reach the next request.
7. **Breaks a supported version.** The package supports PHP 8.2 and later, and Laravel 11, 12 and 13. Language features, framework calls and Saloon header or config values must work on all of them. Env values arrive as strings, so a config value used as an int or bool must be cast.
8. **Breaks the public API without saying so.** Facade methods, factory methods, DTO properties, config keys and the publish tag are used by downstream apps. A breaking change must be called out in the pull request description.
9. **Adds a test that cannot fail or calls the network.** Tests use Saloon's `MockClient` with fixtures and never reach Microsoft. A test must assert the mapped DTO values or the restriction it describes, not only that no exception was thrown.

## Conventions

- A new endpoint follows the steps under "Adding New Endpoints" in `CLAUDE.md`: request class, DTO, test and fixture.
- `README.md` lists new endpoints and any new config or env values.

## Not blocking

Wording, comment polish, alternative designs, and test refactors are optional notes.
