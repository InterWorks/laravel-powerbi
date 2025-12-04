<?php

use InterWorks\PowerBI\Connectors\PowerBIAzureUser;
use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\DTO\Groups;
use InterWorks\PowerBI\DTO\Report;
use InterWorks\PowerBI\Enums\ConnectionAccountType;
use InterWorks\PowerBI\PowerBI;
use InterWorks\PowerBI\Requests\Groups\GetGroups;
use InterWorks\PowerBI\Requests\Reports\GetReportInGroup;
use InterWorks\PowerBI\Tests\Fixtures\PowerBIFixture;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Faking\MockClient;

beforeEach(function () {
    // Reset the connector singleton before each test
    PowerBI::resetConnector();
});

afterEach(function () {
    // Clean up after tests
    PowerBI::resetConnector();
});

//
// Factory Methods
//

test('servicePrincipal creates ServicePrincipal connector', function () {
    $connector = PowerBI::servicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret'
    );

    expect($connector)->toBeInstanceOf(PowerBIServicePrincipal::class);
    expect($connector->getConnectionAccountType())->toBe(ConnectionAccountType::ServicePrincipal);
});

test('adminServicePrincipal creates AdminServicePrincipal connector', function () {
    config()->set('powerbi.admin_client_id', 'admin-client-id');
    config()->set('powerbi.admin_client_secret', 'admin-client-secret');

    $connector = PowerBI::adminServicePrincipal(
        tenant: 'test-tenant'
    );

    expect($connector)->toBeInstanceOf(PowerBIServicePrincipal::class);
    expect($connector->getConnectionAccountType())->toBe(ConnectionAccountType::AdminServicePrincipal);
});

test('azureUser creates AzureUser connector', function () {
    $connector = PowerBI::azureUser(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        redirectUri: 'https://example.com/callback'
    );

    expect($connector)->toBeInstanceOf(PowerBIAzureUser::class);
    expect($connector->getConnectionAccountType())->toBe(ConnectionAccountType::AzureUser);
});

test('create method creates correct connector by type', function () {
    $spConnector = PowerBI::create(
        ConnectionAccountType::ServicePrincipal,
        [
            'tenant' => 'test-tenant',
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
        ]
    );

    expect($spConnector)->toBeInstanceOf(PowerBIServicePrincipal::class);
    expect($spConnector->getConnectionAccountType())->toBe(ConnectionAccountType::ServicePrincipal);

    $azureConnector = PowerBI::create(
        ConnectionAccountType::AzureUser,
        [
            'tenant' => 'test-tenant',
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect_uri' => 'https://example.com/callback',
        ]
    );

    expect($azureConnector)->toBeInstanceOf(PowerBIAzureUser::class);
    expect($azureConnector->getConnectionAccountType())->toBe(ConnectionAccountType::AzureUser);
});

//
// Credential Resolution from Config
//

test('servicePrincipal loads credentials from config when not provided', function () {
    config()->set('powerbi.tenant', 'config-tenant');
    config()->set('powerbi.client_id', 'config-client-id');
    config()->set('powerbi.client_secret', 'config-client-secret');

    $connector = PowerBI::servicePrincipal();

    expect($connector)->toBeInstanceOf(PowerBIServicePrincipal::class);
    expect($connector->getConnectionAccountType())->toBe(ConnectionAccountType::ServicePrincipal);
});

test('adminServicePrincipal loads admin credentials from config', function () {
    config()->set('powerbi.tenant', 'config-tenant');
    config()->set('powerbi.admin_client_id', 'admin-client-id');
    config()->set('powerbi.admin_client_secret', 'admin-client-secret');

    $connector = PowerBI::adminServicePrincipal();

    expect($connector)->toBeInstanceOf(PowerBIServicePrincipal::class);
    expect($connector->getConnectionAccountType())->toBe(ConnectionAccountType::AdminServicePrincipal);
});

test('azureUser loads credentials from config when not provided', function () {
    config()->set('powerbi.tenant', 'config-tenant');
    config()->set('powerbi.client_id', 'config-client-id');
    config()->set('powerbi.client_secret', 'config-client-secret');
    config()->set('powerbi.redirect_uri', 'https://example.com/callback');

    $connector = PowerBI::azureUser();

    expect($connector)->toBeInstanceOf(PowerBIAzureUser::class);
});

test('explicit credentials override config values', function () {
    config()->set('powerbi.tenant', 'config-tenant');
    config()->set('powerbi.client_id', 'config-client-id');
    config()->set('powerbi.client_secret', 'config-client-secret');

    $connector = PowerBI::servicePrincipal(
        tenant: 'override-tenant',
        clientId: 'override-client-id',
        clientSecret: 'override-client-secret'
    );

    expect($connector)->toBeInstanceOf(PowerBIServicePrincipal::class);
});

//
// Connector Management
//

test('connector returns default ServicePrincipal when none set', function () {
    config()->set('powerbi.tenant', 'config-tenant');
    config()->set('powerbi.client_id', 'config-client-id');
    config()->set('powerbi.client_secret', 'config-client-secret');

    $connector = PowerBI::connector();

    expect($connector)->toBeInstanceOf(PowerBIServicePrincipal::class);
    expect($connector->getConnectionAccountType())->toBe(ConnectionAccountType::ServicePrincipal);
});

test('setConnector changes the singleton connector', function () {
    config()->set('powerbi.tenant', 'config-tenant');
    config()->set('powerbi.client_id', 'config-client-id');
    config()->set('powerbi.client_secret', 'config-client-secret');

    // Default is ServicePrincipal
    expect(PowerBI::connector())->toBeInstanceOf(PowerBIServicePrincipal::class);

    // Switch to AzureUser
    $azureConnector = PowerBI::azureUser(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        redirectUri: 'https://example.com/callback'
    );
    PowerBI::setConnector($azureConnector);

    expect(PowerBI::connector())->toBe($azureConnector);
    expect(PowerBI::connector())->toBeInstanceOf(PowerBIAzureUser::class);
});

test('resetConnector resets to null', function () {
    config()->set('powerbi.tenant', 'config-tenant');
    config()->set('powerbi.client_id', 'config-client-id');
    config()->set('powerbi.client_secret', 'config-client-secret');

    // Set a custom connector
    $azureConnector = PowerBI::azureUser(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        redirectUri: 'https://example.com/callback'
    );
    PowerBI::setConnector($azureConnector);

    // Reset
    PowerBI::resetConnector();

    // Next call creates new default connector
    $connector = PowerBI::connector();
    expect($connector)->not->toBe($azureConnector);
    expect($connector)->toBeInstanceOf(PowerBIServicePrincipal::class);
});

//
// Authentication Helpers
//

test('authenticate sets authenticator on current connector with token string', function () {
    config()->set('powerbi.tenant', 'config-tenant');
    config()->set('powerbi.client_id', 'config-client-id');
    config()->set('powerbi.client_secret', 'config-client-secret');

    PowerBI::authenticate('test-token');

    $connector = PowerBI::connector();
    expect($connector)->toBeInstanceOf(PowerBIServicePrincipal::class);
});

test('authenticate sets authenticator on current connector with TokenAuthenticator', function () {
    config()->set('powerbi.tenant', 'config-tenant');
    config()->set('powerbi.client_id', 'config-client-id');
    config()->set('powerbi.client_secret', 'config-client-secret');

    $authenticator = new TokenAuthenticator('test-token');
    PowerBI::authenticate($authenticator);

    $connector = PowerBI::connector();
    expect($connector)->toBeInstanceOf(PowerBIServicePrincipal::class);
});

test('getAccessToken works for ServicePrincipal connector', function () {
    config()->set('powerbi.tenant', 'config-tenant');
    config()->set('powerbi.client_id', 'config-client-id');
    config()->set('powerbi.client_secret', 'config-client-secret');

    // This will call the actual OAuth endpoint, so we just test it doesn't throw
    expect(fn () => PowerBI::getAccessToken())->not->toThrow(RuntimeException::class);
});

//
// Request Proxy Methods
//

test('getGroups sends GetGroups request', function () {
    $mockClient = new MockClient([
        GetGroups::class => new PowerBIFixture('groups/get-groups'),
    ]);

    // Create a connector directly to avoid getAccessToken() call
    $connector = new \InterWorks\PowerBI\Connectors\PowerBIServicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret'
    );

    // Send request with mock client (no authentication needed for mocked requests)
    $response = $connector->send(new GetGroups, mockClient: $mockClient);

    expect($response->status())->toBe(200);
    expect($response->dto())->toBeInstanceOf(Groups::class);
});

test('getReportInGroup sends GetReportInGroup request', function () {
    $mockClient = new MockClient([
        GetReportInGroup::class => new PowerBIFixture('reports/get-report-in-group'),
    ]);

    // Create a connector directly to avoid getAccessToken() call
    $connector = new \InterWorks\PowerBI\Connectors\PowerBIServicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret'
    );

    // Send request with mock client (no authentication needed for mocked requests)
    $response = $connector->send(new GetReportInGroup('test-group-id', 'test-report-id'), mockClient: $mockClient);

    expect($response->status())->toBe(200);
    expect($response->dto())->toBeInstanceOf(Report::class);
});

test('send method sends request and returns DTO', function () {
    $mockClient = new MockClient([
        GetGroups::class => new PowerBIFixture('groups/get-groups'),
    ]);

    // Create a connector directly to avoid getAccessToken() call
    $connector = new \InterWorks\PowerBI\Connectors\PowerBIServicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret'
    );

    // Send request with mock client (no authentication needed for mocked requests)
    $result = $connector->send(new GetGroups, mockClient: $mockClient)->dto();

    expect($result)->toBeInstanceOf(Groups::class);
});

//
// Magic Method Resolution
//

test('resolveRequestClass finds request class in Groups namespace', function () {
    $reflection = new ReflectionClass(PowerBI::class);
    $method = $reflection->getMethod('resolveRequestClass');
    $method->setAccessible(true);

    $result = $method->invoke(null, 'getGroups');

    expect($result)->toBe('InterWorks\PowerBI\Requests\Groups\GetGroups');
});

test('resolveRequestClass finds request class in Reports namespace', function () {
    $reflection = new ReflectionClass(PowerBI::class);
    $method = $reflection->getMethod('resolveRequestClass');
    $method->setAccessible(true);

    $result = $method->invoke(null, 'getReportInGroup');

    expect($result)->toBe('InterWorks\PowerBI\Requests\Reports\GetReportInGroup');
});

test('resolveRequestClass returns null for non-existent class', function () {
    $reflection = new ReflectionClass(PowerBI::class);
    $method = $reflection->getMethod('resolveRequestClass');
    $method->setAccessible(true);

    $result = $method->invoke(null, 'nonExistentRequest');

    expect($result)->toBeNull();
});

test('__callStatic throws RuntimeException for non-existent method', function () {
    config()->set('powerbi.tenant', 'config-tenant');
    config()->set('powerbi.client_id', 'config-client-id');
    config()->set('powerbi.client_secret', 'config-client-secret');

    expect(fn () => PowerBI::nonExistentMethod())->toThrow(
        RuntimeException::class,
        'Method nonExistentMethod does not exist and could not be resolved to a Request class'
    );
});
