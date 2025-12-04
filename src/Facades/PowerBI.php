<?php

namespace InterWorks\PowerBI\Facades;

use Illuminate\Support\Facades\Facade;
use InterWorks\PowerBI\Classes\PowerBIConnectorBase;
use InterWorks\PowerBI\Connectors\PowerBIAzureUser;
use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\DTO\Dashboard;
use InterWorks\PowerBI\DTO\Dashboards;
use InterWorks\PowerBI\DTO\Groups;
use InterWorks\PowerBI\DTO\Report;
use InterWorks\PowerBI\DTO\Reports;
use InterWorks\PowerBI\Enums\ConnectionAccountType;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Request;

/**
 * PowerBI Facade
 *
 * @method static PowerBIServicePrincipal servicePrincipal(?string $tenant = null, ?string $clientId = null, ?string $clientSecret = null) Create a Service Principal connector
 * @method static PowerBIServicePrincipal adminServicePrincipal(?string $tenant = null, ?string $clientId = null, ?string $clientSecret = null) Create an Admin Service Principal connector
 * @method static PowerBIAzureUser azureUser(?string $tenant = null, ?string $clientId = null, ?string $clientSecret = null, ?string $redirectUri = null) Create an Azure User connector
 * @method static PowerBIConnectorBase create(ConnectionAccountType $type, array<string, mixed> $credentials = []) Create a connector by account type
 * @method static PowerBIConnectorBase connector() Get the current singleton connector instance
 * @method static void setConnector(PowerBIConnectorBase $connector) Set the singleton connector instance
 * @method static void resetConnector() Reset the singleton connector to default
 * @method static void authenticate(Authenticator|string $token) Authenticate the current connector
 * @method static mixed getAccessToken(mixed ...$args) Get an access token for the current connector
 * @method static Groups getGroups() Get all groups (workspaces)
 * @method static Reports getReportsInGroup(string $groupId) Get reports in a specific group
 * @method static Report getReportInGroup(string $groupId, string $reportId) Get a specific report in a group
 * @method static Report getReport(string $reportId) Get a specific report by ID (AzureUser only)
 * @method static Dashboards getDashboardsInGroup(string $groupId) Get dashboards in a specific group
 * @method static Dashboard getDashboardInGroup(string $groupId, string $dashboardId) Get a specific dashboard in a group
 * @method static mixed send(Request $request) Send a request using the current connector
 *
 * @see \InterWorks\PowerBI\PowerBI
 */
class PowerBI extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \InterWorks\PowerBI\PowerBI::class;
    }
}
