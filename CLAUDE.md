# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Package Overview

This is a Laravel package that provides a REST API client for Microsoft Power BI. It uses Saloon v3 for HTTP interactions and implements two OAuth2 authentication flows with Azure AD:
- **Client Credentials Grant** for Service Principal authentication (server-to-server)
- **Authorization Code Grant** for Azure User authentication (user-delegated)

**Namespace**: `InterWorks\PowerBI`

## Development Commands

### Testing

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run a single test file
vendor/bin/pest tests/Unit/Requests/Groups/GetGroupsTest.php

# Run a specific test by name
vendor/bin/pest --filter "can get groups"
```

### Code Quality

```bash
# Format code with Laravel Pint
composer format

# Run static analysis (PHPStan level 10)
composer analyse
```

### Package Setup

```bash
# Refresh package discovery
composer prepare
```

## Architecture

### HTTP Client Layer (Saloon)

The package uses a hierarchical connector architecture to support multiple OAuth2 flows:

**Base Connector**:
- **`PowerBIConnectorBase`** (`src/PowerBIConnectorBase.php`): Abstract base class that provides:
  - Base URL resolution (`https://api.powerbi.com/v1.0/myorg`)
  - Request sending with automatic account type restriction enforcement
  - Custom exception handling (admin endpoints, access denied, etc.)
  - Common functionality shared by all connector types

**Concrete Connectors**:
- **`PowerBIServicePrincipal`** (`src/Connectors/PowerBIServicePrincipal.php`):
  - Uses `ClientCredentialsGrant` trait from Saloon
  - Supports `ServicePrinciple` and `AdminServicePrinciple` account types
  - Azure AD v1.0 endpoints with `resource` parameter
  - Token endpoint: `https://login.windows.net/{tenant}/oauth2/token`
  - Server-to-server authentication without user interaction

- **`PowerBIAzureUser`** (`src/Connectors/PowerBIAzureUser.php`):
  - Uses `AuthorizationCodeGrant` trait from Saloon
  - Supports `AzureUser` account type only
  - Azure AD v1.0 endpoints
  - Authorization endpoint: `https://login.microsoftonline.com/{tenant}/oauth2/authorize`
  - Token endpoint: `https://login.microsoftonline.com/{tenant}/oauth2/token`
  - User-delegated authentication with browser redirect
  - Supports access token refresh via refresh tokens

**Factory Class & Facade**:
- **`PowerBI`** (`src/PowerBI.php`): Factory class that provides:
  - Static factory methods: `servicePrinciple()`, `adminServicePrinciple()`, `azureUser()`, `create()`
  - Singleton connector management: `connector()`, `setConnector()`, `resetConnector()`
  - Authentication helpers: `authenticate()`, `getAccessToken()`
  - Direct request methods: `getGroups()`, `getReportsInGroup()`, `getReportInGroup()`, etc.
  - Magic method `__callStatic()` for dynamic request resolution
  - Configuration resolution from `config/powerbi.php` with override support

- **`PowerBI` Facade** (`src/Facades/PowerBI.php`): Laravel facade wrapper providing:
  - Static access to all factory methods
  - Registered as singleton in service container
  - Import via `use InterWorks\PowerBI\Facades\PowerBI;`

### Request/Response Pattern

All API requests follow this structure:

1. **Request classes** extend `Saloon\Http\Request` (e.g., `src/Requests/Groups/GetGroups.php`)
   - Define HTTP method and endpoint
   - Implement `createDtoFromResponse()` to transform responses into DTOs

2. **DTO classes** (Data Transfer Objects) in `src/DTO/`
   - Immutable readonly properties
   - Implement `WithResponse` interface from Saloon
   - Use `HasResponse` trait to attach response metadata
   - Collection DTOs include static `fromArray()` factory methods

Example flow:

```text
PowerBI connector → GetGroups request → Groups DTO → Collection<Group>
```

### Authentication

The package supports two OAuth2 authentication flows:

**1. Client Credentials Grant (Service Principal)**:
- Used for server-to-server authentication
- No user interaction required
- Azure AD v1.0 endpoints
- Token endpoint: `https://login.windows.net/{tenant}/oauth2/token`
- Resource parameter: `https://analysis.windows.net/powerbi/api`
- Environment variables:
  - `POWER_BI_TENANT` - Azure AD tenant ID
  - `POWER_BI_CLIENT_ID` - Standard service principal credentials
  - `POWER_BI_CLIENT_SECRET` - Standard service principal credentials
  - `POWER_BI_ADMIN_CLIENT_ID` - Admin service principal credentials (for admin endpoints)
  - `POWER_BI_ADMIN_CLIENT_SECRET` - Admin service principal credentials

**2. Authorization Code Grant (Azure User)**:
- Used for user-delegated authentication
- Requires user browser interaction and consent
- Uses the same `client_id` and `client_secret` as Service Principal, but with a different OAuth flow
- Azure AD v1.0 endpoints
- Authorization endpoint: `https://login.microsoftonline.com/{tenant}/oauth2/authorize`
- Token endpoint: `https://login.microsoftonline.com/{tenant}/oauth2/token`
- Environment variables:
  - `POWER_BI_TENANT` - Azure AD tenant ID (shared with Service Principal)
  - `POWER_BI_CLIENT_ID` - Application client ID (shared with Service Principal)
  - `POWER_BI_CLIENT_SECRET` - Application client secret (shared with Service Principal)
  - `POWER_BI_REDIRECT_URI` - OAuth callback/redirect URI (unique to Azure User flow)

### Account Type Restrictions

Power BI REST API enforces different access levels based on authentication type:

- **ServicePrinciple**: Can access most endpoints but NOT individual resource endpoints (`/reports/{id}`, `/dashboards/{id}`). Must use group-scoped endpoints instead.
- **AdminServicePrinciple**: Can access admin endpoints (`/admin/*`) and all non-admin endpoints including individual resources.
- **AzureUser**: Can access all non-admin endpoints including individual resource endpoints.

The package automatically enforces these restrictions via the `HasAccountTypeRestrictions` trait. Requests that use this trait will throw `AccountTypeRestrictedException` before making API calls if the account type is not allowed.

### Testing Strategy

Tests use **Saloon's MockClient** with fixture files:

- Fixtures stored in `tests/Fixtures/Saloon/{endpoint}/{action}.json`
- `PowerBIFixture` class handles sensitive data redaction (Authorization headers, IDs, tokens)
- Tests mock both the request class and verify DTO structure
- Environment configuration loaded from `tests/.env` via `TestCase`

**Key pattern**: Tests authenticate the connector, send the request with a mock client, and verify both HTTP status and DTO structure.

## Configuration

The package uses Spatie's `laravel-package-tools` for service provider scaffolding:

- Config file: `config/powerbi.php`
- Service provider: `src/PowerBIServiceProvider.php`
- Facade: `src/Facades/PowerBI.php`

## Caching Configuration

The package caches Power BI API responses using Laravel's default cache store.

### Configuration Options

Configure caching in `config/powerbi.php`:

```php
'cache' => [
    'enabled' => env('POWER_BI_CACHE_ENABLED', true),
    'expiry_seconds' => env('POWER_BI_CACHE_EXPIRY_SECONDS', 3600),
],
```

### Environment Variables

```bash
# Enable/disable caching (default: true)
POWER_BI_CACHE_ENABLED=true

# Cache expiry in seconds, must be >= 1 (default: 3600)
POWER_BI_CACHE_EXPIRY_SECONDS=3600
```

### Cache Store

The package uses Laravel's **default cache store** from `config/cache.php`. To use a specific store (e.g., Redis):

```bash
CACHE_DRIVER=redis
```

### Disabling Caching

To disable caching entirely:

```bash
POWER_BI_CACHE_ENABLED=false
```

### Validation Rules

- `expiry_seconds` must be an integer >= 1
- Setting to 0 or null throws `InvalidArgumentException`
- To disable caching, use `cache.enabled = false`, not `expiry_seconds = 0`

### Caching Behavior

- Responses cached per request URL and parameters
- Cache keys generated automatically by Saloon
- Cache uses the connector's authenticated context

## Static Analysis

PHPStan configuration (`phpstan.neon`):

- Level 10 (maximum strictness)
- Includes baseline for existing issues
- Octane compatibility checks enabled
- Model properties validation enabled

## Usage Examples

The package provides two usage patterns:
1. **Facade Pattern** (recommended): Static methods for quick access
2. **Direct Connector Pattern**: Explicit connector instances for fine-grained control

### Pattern 1: Facade Usage (Recommended)

#### Using Default ServicePrincipal

```php
use InterWorks\PowerBI\Facades\PowerBI;

// Authenticate with default ServicePrincipal (loaded from config)
$token = PowerBI::getAccessToken();
PowerBI::authenticate($token);

// Make direct requests via facade
$groups = PowerBI::getGroups();
$reports = PowerBI::getReportsInGroup('group-id');
$report = PowerBI::getReportInGroup('group-id', 'report-id');
```

#### Switching to AzureUser Connector

```php
use InterWorks\PowerBI\Facades\PowerBI;

// Step 1: Switch to AzureUser connector
// Note: Azure User uses the same client_id/client_secret as Service Principal
// Only the redirect_uri is unique to Azure User flow
$azureConnector = PowerBI::azureUser(
    redirectUri: 'https://your-app.com/callback'
);
PowerBI::setConnector($azureConnector);

// Generate authorization URL
$authUrl = $azureConnector->getAuthorizationUrl();
$state = $azureConnector->getState();
session(['oauth_state' => $state]);
return redirect($authUrl);

// Step 2: Handle OAuth callback
$code = request()->get('code');
$state = request()->get('state');
$sessionState = session('oauth_state');

$token = PowerBI::getAccessToken($code, $state, $sessionState);
PowerBI::authenticate($token);

// Step 3: Make API calls (now uses AzureUser)
$report = PowerBI::getReport('report-id'); // Works with AzureUser
$dashboards = PowerBI::getDashboardsInGroup('group-id');
```

#### Using AdminServicePrincipal

```php
use InterWorks\PowerBI\Facades\PowerBI;

// Switch to admin connector
$adminConnector = PowerBI::adminServicePrinciple();
PowerBI::setConnector($adminConnector);

// Authenticate
$token = PowerBI::getAccessToken();
PowerBI::authenticate($token);

// Access admin endpoints
$allGroups = PowerBI::send(new GetGroupsAsAdmin());
```

### Pattern 2: Direct Connector Usage

#### Service Principal (Client Credentials)

```php
use InterWorks\PowerBI\PowerBI;
use InterWorks\PowerBI\Requests\Groups\GetGroups;

// Create connector with explicit credentials
$connector = PowerBI::servicePrinciple(
    tenant: 'your-tenant-id',
    clientId: 'your-client-id',
    clientSecret: 'your-client-secret'
);

// Or load from config
$connector = PowerBI::servicePrinciple();

// Authenticate
$token = $connector->getAccessToken();
$connector->authenticate($token);

// Make API calls
$request = new GetGroups();
$response = $connector->send($request);
$groups = $response->dto();
```

#### Admin Service Principal

```php
use InterWorks\PowerBI\PowerBI;
use InterWorks\PowerBI\Requests\Admin\Groups\GetGroupsAsAdmin;

// Using convenience method (loads admin credentials from config)
$connector = PowerBI::adminServicePrinciple();

// Authenticate
$token = $connector->getAccessToken();
$connector->authenticate($token);

// Access admin endpoints
$request = new GetGroupsAsAdmin();
$response = $connector->send($request);
```

#### Azure User (Authorization Code)

```php
use InterWorks\PowerBI\PowerBI;
use InterWorks\PowerBI\Requests\Reports\GetReport;

// Step 1: Generate authorization URL
// Note: Azure User uses the same client_id and client_secret as Service Principal
// Only the redirect_uri is unique to the Azure User flow
$connector = PowerBI::azureUser(
    redirectUri: 'https://your-app.com/callback'
);
// Or with explicit credentials:
// $connector = PowerBI::azureUser(
//     tenant: 'your-tenant-id',
//     clientId: 'your-client-id',        // Same as Service Principal
//     clientSecret: 'your-client-secret', // Same as Service Principal
//     redirectUri: 'https://your-app.com/callback'
// );

$authUrl = $connector->getAuthorizationUrl();
$state = $connector->getState();

// Store $state in session
session(['oauth_state' => $state]);

// Redirect user to $authUrl
return redirect($authUrl);

// Step 2: Handle callback (in your redirect URI controller)
$code = request()->get('code');
$state = request()->get('state');
$sessionState = session('oauth_state');

// Exchange code for token
$token = $connector->getAccessToken($code, $state, $sessionState);
$connector->authenticate($token);

// Step 3: Store token for later use
cache()->put("powerbi_token_{$userId}", serialize($token), $token->getExpiresAt());

// Step 4: Retrieve and refresh token when needed
$token = unserialize(cache()->get("powerbi_token_{$userId}"));
if ($token->hasExpired()) {
    $token = $connector->refreshAccessToken($token);
    cache()->put("powerbi_token_{$userId}", serialize($token), $token->getExpiresAt());
}
$connector->authenticate($token);

// Make API calls (AzureUser can access individual resource endpoints)
$request = new GetReport('report-id');
$response = $connector->send($request);
```

## Adding New Endpoints

When adding a new Power BI endpoint:

1. Create request class in `src/Requests/{Category}/{Action}.php`
2. Create DTO(s) in `src/DTO/` with readonly properties
3. Implement `createDtoFromResponse()` in request class with PHPStan type annotations
4. **If endpoint has account type restrictions**, use the `HasAccountTypeRestrictions` trait:
   ```php
   use InterWorks\PowerBI\Requests\Concerns\HasAccountTypeRestrictions;

   class GetReport extends Request
   {
       use HasAccountTypeRestrictions;

       public function restrictedAccountTypes(): array
       {
           return [ConnectionAccountType::ServicePrinciple];
       }
   }
   ```
   The connector will automatically enforce restrictions before sending the request.
5. Add test in `tests/Unit/Requests/{Category}/{Action}Test.php`
6. If using `HasAccountTypeRestrictions`, add tests for each account type to verify access control
7. Create fixture JSON in `tests/Fixtures/Saloon/{category}/{action}.json`
8. Run `composer format` and `composer analyse` before committing
