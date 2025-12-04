<?php

namespace InterWorks\PowerBI;

use Illuminate\Support\Facades\Config;
use InterWorks\PowerBI\Classes\PowerBIConnectorBase;
use InterWorks\PowerBI\Connectors\PowerBIAzureUser;
use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\DTO\Dashboard;
use InterWorks\PowerBI\DTO\Dashboards;
use InterWorks\PowerBI\DTO\Groups;
use InterWorks\PowerBI\DTO\Report;
use InterWorks\PowerBI\DTO\Reports;
use InterWorks\PowerBI\Enums\ConnectionAccountType;
use InterWorks\PowerBI\Requests\Dashboards\GetDashboardInGroup;
use InterWorks\PowerBI\Requests\Dashboards\GetDashboardsInGroup;
use InterWorks\PowerBI\Requests\Groups\GetGroups;
use InterWorks\PowerBI\Requests\Reports\GetReport;
use InterWorks\PowerBI\Requests\Reports\GetReportInGroup;
use InterWorks\PowerBI\Requests\Reports\GetReportsInGroup;
use InvalidArgumentException;
use RuntimeException;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Request;

/**
 * PowerBI Factory Class
 *
 * Provides static factory methods for creating Power BI connectors and
 * convenience methods for making common API requests.
 *
 * Usage:
 *   // Create connectors
 *   $connector = PowerBI::servicePrincipal();
 *   $connector = PowerBI::adminServicePrincipal();
 *   $connector = PowerBI::azureUser();
 *
 *   // Direct requests using default connector
 *   PowerBI::authenticate($token);
 *   $groups = PowerBI::getGroups();
 *   $report = PowerBI::getReportInGroup('group-id', 'report-id');
 */
class PowerBI
{
    /** @var PowerBIConnectorBase|null Singleton connector instance */
    protected static ?PowerBIConnectorBase $connector = null;

    //
    // Factory Methods
    //

    /**
     * Create a Service Principal connector.
     *
     * Uses Client Credentials Grant for server-to-server authentication.
     * Loads credentials from config if not provided.
     *
     * @param  string|null  $tenant  Azure AD tenant ID (defaults to config)
     * @param  string|null  $clientId  Application client ID (defaults to config)
     * @param  string|null  $clientSecret  Application client secret (defaults to config)
     */
    public static function servicePrincipal(
        ?string $tenant = null,
        ?string $clientId = null,
        ?string $clientSecret = null
    ): PowerBIServicePrincipal {
        return new PowerBIServicePrincipal(
            tenant: $tenant,
            clientId: $clientId,
            clientSecret: $clientSecret,
            connectionAccountType: ConnectionAccountType::ServicePrincipal
        );
    }

    /**
     * Create an Admin Service Principal connector.
     *
     * Uses Client Credentials Grant with admin-level credentials.
     * Required for accessing admin endpoints (/admin/*).
     * Loads admin credentials from config if not provided.
     *
     * @param  string|null  $tenant  Azure AD tenant ID (defaults to config)
     * @param  string|null  $clientId  Admin application client ID (defaults to config admin_client_id)
     * @param  string|null  $clientSecret  Admin application client secret (defaults to config admin_client_secret)
     */
    public static function adminServicePrincipal(
        ?string $tenant = null,
        ?string $clientId = null,
        ?string $clientSecret = null
    ): PowerBIServicePrincipal {
        /** @var string $configClientId */
        $configClientId = Config::get('powerbi.admin_client_id', '');
        /** @var string $configClientSecret */
        $configClientSecret = Config::get('powerbi.admin_client_secret', '');

        return new PowerBIServicePrincipal(
            tenant: $tenant,
            clientId: $clientId ?? $configClientId,
            clientSecret: $clientSecret ?? $configClientSecret,
            connectionAccountType: ConnectionAccountType::AdminServicePrincipal
        );
    }

    /**
     * Create an Azure User connector.
     *
     * Uses Authorization Code Grant for user-delegated authentication.
     * Requires browser-based OAuth flow with user consent.
     * Uses the same client credentials as Service Principal but with a different OAuth flow.
     * Loads credentials from config if not provided.
     *
     * @param  string|null  $tenant  Azure AD tenant ID (defaults to config)
     * @param  string|null  $clientId  Application client ID (defaults to config client_id)
     * @param  string|null  $clientSecret  Application client secret (defaults to config client_secret)
     * @param  string|null  $redirectUri  OAuth callback URI (defaults to config redirect_uri)
     */
    public static function azureUser(
        ?string $tenant = null,
        ?string $clientId = null,
        ?string $clientSecret = null,
        ?string $redirectUri = null
    ): PowerBIAzureUser {
        /** @var string $configTenant */
        $configTenant = Config::get('powerbi.tenant', '');
        /** @var string $configClientId */
        $configClientId = Config::get('powerbi.client_id', '');
        /** @var string $configClientSecret */
        $configClientSecret = Config::get('powerbi.client_secret', '');
        /** @var string $configRedirectUri */
        $configRedirectUri = Config::get('powerbi.redirect_uri', '');

        return new PowerBIAzureUser(
            tenant: $tenant ?? $configTenant,
            clientId: $clientId ?? $configClientId,
            clientSecret: $clientSecret ?? $configClientSecret,
            redirectUri: $redirectUri ?? $configRedirectUri
        );
    }

    /**
     * Create a connector by account type.
     *
     * Factory method that creates the appropriate connector based on the
     * specified ConnectionAccountType enum value.
     *
     * @param  ConnectionAccountType  $type  The account type
     * @param  array<string, mixed>  $credentials  Optional credential overrides
     *
     * @throws InvalidArgumentException When credentials are missing for AzureUser
     */
    public static function create(
        ConnectionAccountType $type,
        array $credentials = []
    ): PowerBIConnectorBase {
        return match ($type) {
            ConnectionAccountType::ServicePrincipal => static::servicePrincipal(
                tenant: isset($credentials['tenant']) && is_string($credentials['tenant']) ? $credentials['tenant'] : null,
                clientId: isset($credentials['client_id']) && is_string($credentials['client_id']) ? $credentials['client_id'] : null,
                clientSecret: isset($credentials['client_secret']) && is_string($credentials['client_secret']) ? $credentials['client_secret'] : null
            ),
            ConnectionAccountType::AdminServicePrincipal => static::adminServicePrincipal(
                tenant: isset($credentials['tenant']) && is_string($credentials['tenant']) ? $credentials['tenant'] : null,
                clientId: isset($credentials['client_id']) && is_string($credentials['client_id']) ? $credentials['client_id'] : null,
                clientSecret: isset($credentials['client_secret']) && is_string($credentials['client_secret']) ? $credentials['client_secret'] : null
            ),
            ConnectionAccountType::AzureUser => static::azureUser(
                tenant: isset($credentials['tenant']) && is_string($credentials['tenant']) ? $credentials['tenant'] : null,
                clientId: isset($credentials['client_id']) && is_string($credentials['client_id']) ? $credentials['client_id'] : null,
                clientSecret: isset($credentials['client_secret']) && is_string($credentials['client_secret']) ? $credentials['client_secret'] : null,
                redirectUri: isset($credentials['redirect_uri']) && is_string($credentials['redirect_uri']) ? $credentials['redirect_uri'] : null
            ),
        };
    }

    //
    // Connector Management
    //

    /**
     * Get the current singleton connector instance.
     *
     * Creates a default Service Principal connector if none exists.
     */
    public static function connector(): PowerBIConnectorBase
    {
        if (static::$connector === null) {
            static::$connector = static::servicePrincipal();
        }

        return static::$connector;
    }

    /**
     * Set the singleton connector instance.
     *
     * Use this to switch between connector types at runtime.
     *
     * @param  PowerBIConnectorBase  $connector  The connector to use
     */
    public static function setConnector(PowerBIConnectorBase $connector): void
    {
        static::$connector = $connector;
    }

    /**
     * Reset the singleton connector to default Service Principal.
     */
    public static function resetConnector(): void
    {
        static::$connector = null;
    }

    //
    // Authentication Helpers
    //

    /**
     * Authenticate the current connector.
     *
     * @param  Authenticator|string  $token  OAuth token or authenticator
     */
    public static function authenticate(Authenticator|string $token): void
    {
        if (is_string($token)) {
            $token = new TokenAuthenticator($token);
        }

        static::connector()->authenticate($token);
    }

    /**
     * Get an access token for the current connector.
     *
     * For Service Principal: Returns the access token directly.
     * For Azure User: Pass authorization code, state, and session state.
     *
     * @param  mixed  ...$args  Arguments passed to the connector's getAccessToken method
     *
     * @throws RuntimeException When the connector doesn't support getting access tokens
     */
    public static function getAccessToken(mixed ...$args): mixed
    {
        $connector = static::connector();

        if (! method_exists($connector, 'getAccessToken')) {
            throw new RuntimeException(
                'Current connector does not support getAccessToken method'
            );
        }

        return $connector->getAccessToken(...$args);
    }

    //
    // Common Request Methods
    //

    /**
     * Get all groups (workspaces).
     */
    public static function getGroups(): Groups
    {
        /** @var Groups */
        return static::send(new GetGroups);
    }

    /**
     * Get reports in a specific group.
     *
     * @param  string  $groupId  The group (workspace) ID
     */
    public static function getReportsInGroup(string $groupId): Reports
    {
        /** @var Reports */
        return static::send(new GetReportsInGroup($groupId));
    }

    /**
     * Get a specific report in a group.
     *
     * @param  string  $groupId  The group (workspace) ID
     * @param  string  $reportId  The report ID
     */
    public static function getReportInGroup(string $groupId, string $reportId): Report
    {
        /** @var Report */
        return static::send(new GetReportInGroup($groupId, $reportId));
    }

    /**
     * Get a specific report by ID (AzureUser only).
     *
     * Note: This endpoint is restricted to AzureUser account type.
     * Service Principal accounts must use getReportInGroup instead.
     *
     * @param  string  $reportId  The report ID
     *
     * @throws \InterWorks\PowerBI\Exceptions\AccountTypeRestrictedException
     */
    public static function getReport(string $reportId): Report
    {
        /** @var Report */
        return static::send(new GetReport($reportId));
    }

    /**
     * Get dashboards in a specific group.
     *
     * @param  string  $groupId  The group (workspace) ID
     */
    public static function getDashboardsInGroup(string $groupId): Dashboards
    {
        /** @var Dashboards */
        return static::send(new GetDashboardsInGroup($groupId));
    }

    /**
     * Get a specific dashboard in a group.
     *
     * @param  string  $groupId  The group (workspace) ID
     * @param  string  $dashboardId  The dashboard ID
     */
    public static function getDashboardInGroup(string $groupId, string $dashboardId): Dashboard
    {
        /** @var Dashboard */
        return static::send(new GetDashboardInGroup($groupId, $dashboardId));
    }

    //
    // Low-Level Request Sending
    //

    /**
     * Send a request using the current connector.
     *
     * @param  Request  $request  The Saloon request instance
     * @return mixed The DTO from createDtoFromResponse
     */
    public static function send(Request $request): mixed
    {
        $response = static::connector()->send($request);

        return $response->dto();
    }

    /**
     * Magic method to dynamically proxy request methods.
     *
     * Converts method calls like PowerBI::getSomething($args) into
     * Request class instantiation and sending.
     *
     * @param  string  $method  The method name
     * @param  array<mixed>  $args  The method arguments
     *
     * @throws RuntimeException When the request class cannot be found or instantiated
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        // Try to map the method to a Request class
        $requestClass = static::resolveRequestClass($method);

        if ($requestClass === null) {
            throw new RuntimeException(
                "Method {$method} does not exist and could not be resolved to a Request class"
            );
        }

        // Instantiate the request with the provided arguments
        $request = new $requestClass(...$args);

        // Send the request
        return static::send($request);
    }

    /**
     * Resolve a method name to a Request class.
     *
     * Attempts to find a Request class matching the method name pattern.
     * For example: 'getGroups' -> GetGroups, 'getReportInGroup' -> GetReportInGroup
     *
     * @param  string  $method  The method name
     * @return class-string<Request>|null The fully qualified Request class name or null
     */
    protected static function resolveRequestClass(string $method): ?string
    {
        // Convert camelCase method to PascalCase class name
        $className = ucfirst($method);

        // Common request namespaces to search
        $namespaces = [
            "InterWorks\\PowerBI\\Requests\\Groups\\{$className}",
            "InterWorks\\PowerBI\\Requests\\Reports\\{$className}",
            "InterWorks\\PowerBI\\Requests\\Dashboards\\{$className}",
            "InterWorks\\PowerBI\\Requests\\EmbedToken\\{$className}",
            "InterWorks\\PowerBI\\Requests\\Admin\\Groups\\{$className}",
        ];

        foreach ($namespaces as $fqcn) {
            if (class_exists($fqcn)) {
                /** @var class-string<Request> */
                return $fqcn;
            }
        }

        return null;
    }
}
