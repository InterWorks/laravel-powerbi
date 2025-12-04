<?php

use InterWorks\PowerBI\Connectors\PowerBIAzureUser;
use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\DTO\Groups;
use InterWorks\PowerBI\Enums\ConnectionAccountType;
use InterWorks\PowerBI\Facades\PowerBI as PowerBIFacade;
use InterWorks\PowerBI\PowerBI;
use InterWorks\PowerBI\Requests\Groups\GetGroups;
use InterWorks\PowerBI\Tests\Fixtures\PowerBIFixture;
use Saloon\Http\Faking\MockClient;

beforeEach(function () {
    // Set up config
    config()->set('powerbi.tenant', 'test-tenant');
    config()->set('powerbi.client_id', 'test-client-id');
    config()->set('powerbi.client_secret', 'test-client-secret');
    config()->set('powerbi.admin_client_id', 'admin-client-id');
    config()->set('powerbi.admin_client_secret', 'admin-client-secret');
    config()->set('powerbi.redirect_uri', 'https://example.com/callback');

    // Reset singleton
    PowerBI::resetConnector();
});

afterEach(function () {
    PowerBI::resetConnector();
});

//
// Facade Resolution
//

test('facade resolves from container', function () {
    $factory = app(PowerBI::class);

    expect($factory)->toBeInstanceOf(PowerBI::class);
});

test('facade is registered as singleton', function () {
    $factory1 = app(PowerBI::class);
    $factory2 = app(PowerBI::class);

    expect($factory1)->toBe($factory2);
});

test('facade accessor returns correct class name', function () {
    $reflection = new ReflectionClass(PowerBIFacade::class);
    $method = $reflection->getMethod('getFacadeAccessor');
    $method->setAccessible(true);

    $accessor = $method->invoke(new PowerBIFacade);

    expect($accessor)->toBe(PowerBI::class);
});

//
// Facade Factory Methods
//

test('facade servicePrincipal method creates ServicePrincipal connector', function () {
    $connector = PowerBIFacade::servicePrincipal();

    expect($connector)->toBeInstanceOf(PowerBIServicePrincipal::class);
    expect($connector->getConnectionAccountType())->toBe(ConnectionAccountType::ServicePrincipal);
});

test('facade adminServicePrincipal method creates AdminServicePrincipal connector', function () {
    $connector = PowerBIFacade::adminServicePrincipal();

    expect($connector)->toBeInstanceOf(PowerBIServicePrincipal::class);
    expect($connector->getConnectionAccountType())->toBe(ConnectionAccountType::AdminServicePrincipal);
});

test('facade azureUser method creates AzureUser connector', function () {
    $connector = PowerBIFacade::azureUser();

    expect($connector)->toBeInstanceOf(PowerBIAzureUser::class);
    expect($connector->getConnectionAccountType())->toBe(ConnectionAccountType::AzureUser);
});

test('facade create method works with account type', function () {
    $connector = PowerBIFacade::create(ConnectionAccountType::ServicePrincipal);

    expect($connector)->toBeInstanceOf(PowerBIServicePrincipal::class);
});

//
// Facade Connector Management
//

test('facade connector method returns default connector', function () {
    $connector = PowerBIFacade::connector();

    expect($connector)->toBeInstanceOf(PowerBIServicePrincipal::class);
});

test('facade setConnector changes singleton connector', function () {
    // Create and set AzureUser connector
    $azureConnector = PowerBIFacade::azureUser();
    PowerBIFacade::setConnector($azureConnector);

    // Verify it's now the active connector
    expect(PowerBIFacade::connector())->toBe($azureConnector);
    expect(PowerBIFacade::connector())->toBeInstanceOf(PowerBIAzureUser::class);
});

test('facade resetConnector resets singleton', function () {
    // Set custom connector
    $azureConnector = PowerBIFacade::azureUser();
    PowerBIFacade::setConnector($azureConnector);

    // Reset
    PowerBIFacade::resetConnector();

    // Next call creates new default
    $connector = PowerBIFacade::connector();
    expect($connector)->not->toBe($azureConnector);
    expect($connector)->toBeInstanceOf(PowerBIServicePrincipal::class);
});

//
// Facade Authentication
//

test('facade authenticate method works', function () {
    PowerBIFacade::authenticate('test-token');

    $connector = PowerBIFacade::connector();
    expect($connector)->toBeInstanceOf(PowerBIServicePrincipal::class);
});

test('facade getAccessToken method works', function () {
    // This calls the actual OAuth endpoint, just verify it doesn't throw
    expect(fn () => PowerBIFacade::getAccessToken())->not->toThrow(RuntimeException::class);
});

//
// Facade Request Methods
//

test('facade can send requests and return DTOs', function () {
    $mockClient = new MockClient([
        GetGroups::class => new PowerBIFixture('groups/get-groups'),
    ]);

    // Create a test connector to avoid getAccessToken() call
    $testConnector = new \InterWorks\PowerBI\Connectors\PowerBIServicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret'
    );
    PowerBIFacade::setConnector($testConnector);

    // Send via connector (facade doesn't directly expose send with mockClient)
    $connector = PowerBIFacade::connector();
    $result = $connector->send(new GetGroups, mockClient: $mockClient)->dto();

    expect($result)->toBeInstanceOf(Groups::class);
});

//
// Integration Tests
//

test('facade maintains state across multiple calls', function () {
    // Create custom connector
    $customConnector = PowerBIFacade::servicePrincipal(
        tenant: 'custom-tenant',
        clientId: 'custom-client-id',
        clientSecret: 'custom-client-secret'
    );

    PowerBIFacade::setConnector($customConnector);

    // Multiple calls should use same connector
    expect(PowerBIFacade::connector())->toBe($customConnector);
    expect(PowerBIFacade::connector())->toBe($customConnector);
});

test('facade works with credential overrides', function () {
    $connector = PowerBIFacade::servicePrincipal(
        tenant: 'override-tenant',
        clientId: 'override-client-id',
        clientSecret: 'override-client-secret'
    );

    expect($connector)->toBeInstanceOf(PowerBIServicePrincipal::class);
});

test('facade switching between connector types works correctly', function () {
    // Start with ServicePrincipal (default)
    $spConnector = PowerBIFacade::connector();
    expect($spConnector)->toBeInstanceOf(PowerBIServicePrincipal::class);
    expect($spConnector->getConnectionAccountType())->toBe(ConnectionAccountType::ServicePrincipal);

    // Switch to AzureUser
    $azureConnector = PowerBIFacade::azureUser();
    PowerBIFacade::setConnector($azureConnector);

    expect(PowerBIFacade::connector())->toBe($azureConnector);
    expect(PowerBIFacade::connector())->toBeInstanceOf(PowerBIAzureUser::class);
    expect(PowerBIFacade::connector()->getConnectionAccountType())->toBe(ConnectionAccountType::AzureUser);

    // Switch to AdminServicePrincipal
    $adminConnector = PowerBIFacade::adminServicePrincipal();
    PowerBIFacade::setConnector($adminConnector);

    expect(PowerBIFacade::connector())->toBe($adminConnector);
    expect(PowerBIFacade::connector())->toBeInstanceOf(PowerBIServicePrincipal::class);
    expect(PowerBIFacade::connector()->getConnectionAccountType())->toBe(ConnectionAccountType::AdminServicePrincipal);

    // Reset back to default
    PowerBIFacade::resetConnector();
    expect(PowerBIFacade::connector())->not->toBe($azureConnector);
    expect(PowerBIFacade::connector())->not->toBe($adminConnector);
    expect(PowerBIFacade::connector()->getConnectionAccountType())->toBe(ConnectionAccountType::ServicePrincipal);
});
