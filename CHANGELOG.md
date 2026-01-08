# Changelog

All notable changes to `laravel-powerbi` will be documented in this file.

## v0.0.1 - 2026-01-08

### Initial Release

This is the first public release of `laravel-powerbi`, a comprehensive Laravel package for interacting with the Microsoft Power BI REST API.

### Added

#### Core Features

- **REST API Client** built on Saloon v3 for Microsoft Power BI
- **Three OAuth2 Authentication Flows**:
  - Service Principal (Client Credentials Grant) for server-to-server authentication
  - Admin Service Principal for tenant-wide administrative operations
  - Azure User (Authorization Code Grant) for user-delegated authentication
  
- **Factory Pattern** with `PowerBI` class providing static factory methods and singleton connector management
- **Laravel Facade** (`PowerBI`) for convenient static access to all functionality
- **Hierarchical Connector Architecture**:
  - `PowerBIConnectorBase` abstract base class with shared functionality
  - `PowerBIServicePrincipal` connector for client credentials flow
  - `PowerBIAzureUser` connector for authorization code flow
  

#### API Endpoints

- **Groups (Workspaces)**:
  - `GetGroups` - Retrieve all accessible groups
  - `GetGroupsAsAdmin` - Admin endpoint for tenant-wide group access with expansions
  
- **Reports**:
  - `GetReportsInGroup` - Get all reports in a group
  - `GetReportInGroup` - Get specific report within a group
  - `GetReport` - Get report by ID (Azure User only)
  
- **Dashboards**:
  - `GetDashboardsInGroup` - Get all dashboards in a group
  - `GetDashboardInGroup` - Get specific dashboard within a group
  
- **Embed Tokens**:
  - `ReportsGenerateTokenInGroup` - Generate embed token for reports
  - `DashboardsGenerateTokenInGroup` - Generate embed token for dashboards
  
- **Admin Endpoints**:
  - `GetGroupsAsAdmin` - Administrative access to all groups
  - `GetUserArtifactAccessAsAdmin` - Get all artifacts a user has access to
  

#### Data Transfer Objects (DTOs)

- **Type-safe immutable DTOs** with readonly properties
- **Full IDE autocomplete support** for all response properties
- **Collection DTOs** using Laravel Collections:
  - `Groups` - Collection of `Group` objects
  - `Reports` - Collection of `Report` objects
  - `Dashboards` - Collection of `Dashboard` objects
  - `ArtifactAccessResponse` - Collection of `ArtifactAccessEntry` objects
  
- **Individual resource DTOs**:
  - `Group` - Group/workspace metadata
  - `Report` - Report metadata with embed URLs
  - `Dashboard` - Dashboard metadata
  - `EmbedToken` - Embed token with expiration (Carbon instance)
  - `ArtifactAccessEntry` - Artifact access details
  
- **Response metadata access** via `WithResponse` interface

#### Caching System

- **Automatic response caching** using Saloon's cache plugin
- **Laravel cache integration** using default cache store
- **Configurable cache TTL** via environment variables
- **Cache status indicators** on response objects
- **Validation** for cache configuration (expiry must be ≥ 1 second)

#### Account Type Restrictions

- **Automatic enforcement** of Power BI API access restrictions
- **Three account types**:
  - `ServicePrincipal` - Standard service principal access
  - `AdminServicePrincipal` - Administrative access
  - `AzureUser` - User-delegated access
  
- **Pre-flight validation** throwing `AccountTypeRestrictedException` before API calls
- **`HasAccountTypeRestrictions` trait** for request classes
- **Clear error messages** indicating which account types can access each endpoint

#### Pagination Support

- **Continuation token handling** for paginated admin endpoints
- **`HasContinuationTokenPagination` trait** for automatic pagination
- **`getAllPages()` method** to fetch all pages automatically
- **Laravel Collection** combining results from all pages

#### Error Handling

- **Custom exceptions**:
  - `AccountTypeRestrictedException` - Account type access violations
  - `UnauthorizedAdminAccessException` - Admin endpoint authentication failures
  
- **Enhanced error messages** with context about restrictions and solutions

#### Configuration & Service Provider

- **Publishable configuration** file (`config/powerbi.php`)
- **Service provider** using Spatie's laravel-package-tools
- **Environment variable support** for all configuration options
- **Validation** for required configuration values

#### Testing Infrastructure

- **Comprehensive test suite** using Pest PHP
- **Saloon MockClient integration** for mocking HTTP responses
- **Fixture system** with automatic sensitive data redaction
- **Account type restriction tests** for access control validation
- **PHPStan level 10** static analysis
- **Laravel Pint** code formatting
- **Test helpers** in `TestCase` base class
- **Environment configuration** via `tests/.env`

### Requirements

- PHP 8.1 or higher
- Laravel 9.x, 10.x, 11.x, or 12.x
- Saloon v3
- Microsoft Power BI Pro or Premium account
- Azure AD application registration with Power BI API permissions

### Notes

This is a beta release (0.0.x) - the API may change before reaching 1.0.0 stability.
