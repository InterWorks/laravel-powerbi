# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Package Overview

This is a Laravel package that provides a REST API client for Microsoft Power BI. It uses Saloon v3 for HTTP interactions and implements OAuth2 Client Credentials authentication flow with Azure AD.

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

The package extends Saloon's `Connector` class to create an authenticated HTTP client:

- **`PowerBI`** (`src/PowerBI.php`): Main connector class that handles:
  - Base URL resolution (`https://api.powerbi.com/v1.0/myorg`)
  - OAuth2 client credentials authentication with Azure AD
  - Configuration from `config/powerbi.php` (tenant, client_id, client_secret)
  - Request modifier to add Power BI resource URL to token requests

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

OAuth2 flow with Azure AD:

- Uses `ClientCredentialsGrant` trait from Saloon
- Token endpoint: `https://login.windows.net/{tenant}/oauth2/token`
- Resource: `https://analysis.windows.net/powerbi/api`
- Credentials configured via environment variables:
  - `POWER_BI_TENANT`
  - `POWER_BI_CLIENT_ID`
  - `POWER_BI_CLIENT_SECRET`

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

## Static Analysis

PHPStan configuration (`phpstan.neon`):

- Level 10 (maximum strictness)
- Includes baseline for existing issues
- Octane compatibility checks enabled
- Model properties validation enabled

## Adding New Endpoints

When adding a new Power BI endpoint:

1. Create request class in `src/Requests/{Category}/{Action}.php`
2. Create DTO(s) in `src/DTO/` with readonly properties
3. Implement `createDtoFromResponse()` in request class with PHPStan type annotations
4. Add test in `tests/Unit/Requests/{Category}/{Action}Test.php`
5. Create fixture JSON in `tests/Fixtures/Saloon/{category}/{action}.json`
6. Run `composer format` and `composer analyse` before committing
