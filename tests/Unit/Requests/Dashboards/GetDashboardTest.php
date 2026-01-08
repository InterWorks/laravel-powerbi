<?php

use InterWorks\PowerBI\Connectors\PowerBIAzureUser;
use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\DTO\Dashboard;
use InterWorks\PowerBI\Enums\ConnectionAccountType;
use InterWorks\PowerBI\Exceptions\AccountTypeRestrictedException;
use InterWorks\PowerBI\PowerBI;
use InterWorks\PowerBI\Requests\Dashboards\GetDashboard;
use InterWorks\PowerBI\Tests\Fixtures\PowerBIFixture;
use Saloon\Http\Faking\MockClient;

test('can get single dashboard', function () {
    $mockClient = new MockClient([
        GetDashboard::class => new PowerBIFixture('dashboards/get-dashboard'),
    ]);

    // Create the PowerBI connection with AdminServicePrincipal (has access to GetDashboard)
    // Or use AzureUser - both have access to this endpoint
    $powerBIConnection = new PowerBIAzureUser(
        tenant: env('POWER_BI_TENANT'),
        clientId: env('POWER_BI_CLIENT_ID'),
        clientSecret: env('POWER_BI_CLIENT_SECRET'),
        redirectUri: 'https://fakeurl.non/callback'
    );

    // REMINDER: We cannot test the full OAuth flow due to the need for user interaction/redirect; this step is skipped.
    // $authenticator = $powerBIConnection->getAuthorizationUrl('fake-code', 'fake-state');
    // $powerBIConnection->authenticate($authenticator);

    // Send the request
    $request = new GetDashboard(env('POWER_BI_DASHBOARD_ID'));
    $response = $powerBIConnection->send($request, mockClient: $mockClient);

    // Validate the response
    expect($response->status())->toBe(200);
    expect($response->dto())->toBeInstanceOf(Dashboard::class);

    // Validate the dashboard properties
    $dashboard = $response->dto();
    expect($dashboard)->toBeInstanceOf(Dashboard::class);
    expect($dashboard->id)->toBeString();
    expect($dashboard->displayName)->toBeString();
    expect($dashboard->isReadOnly)->toBeBool();
    expect($dashboard->embedUrl)->toBeString();
});

test('GetDashboard access control - allows AzureUser to access GetDashboard', function () {
    $mockClient = new MockClient([
        GetDashboard::class => new PowerBIFixture('dashboards/get-dashboard'),
    ]);
    // Create connection with AzureUser account type using factory method
    $powerBIConnection = new PowerBIAzureUser(
        tenant: env('POWER_BI_TENANT'),
        clientId: env('POWER_BI_CLIENT_ID'),
        clientSecret: env('POWER_BI_CLIENT_SECRET'),
        redirectUri: 'https://localhost/oauth/callback'
    );

    // Creating and attempting to send should NOT throw AccountTypeRestrictedException
    // If it throws something else (auth error, etc), that's fine - we only care about access control
    $request = new GetDashboard(env('POWER_BI_DASHBOARD_ID'));

    try {
        // We don't care if auth fails - we just want to verify the middleware doesn't block
        $powerBIConnection->send($request, mockClient: $mockClient);
    } catch (AccountTypeRestrictedException $e) {
        // This should NOT happen for AzureUser
        throw $e;
    } catch (\Exception $e) {
        // Any other exception is fine - we're only testing the middleware didn't block it
    }

    // If we got here, the middleware didn't throw AccountTypeRestrictedException
    expect(true)->toBeTrue();
});

test('GetDashboard access control - throws exception when ServicePrincipal attempts to access GetDashboard', function () {
    // Create connection with ServicePrincipal account type
    $powerBIConnection = new PowerBIServicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        connectionAccountType: ConnectionAccountType::ServicePrincipal
    );

    // No need to authenticate - the restriction check happens before the HTTP request
    // Attempt to send the request - should throw before making API call
    $request = new GetDashboard('test-dashboard-id');

    expect(fn () => $powerBIConnection->send($request))
        ->toThrow(AccountTypeRestrictedException::class, "Account type 'ServicePrincipal' cannot access GET /dashboards/test-dashboard-id");
});

test('GetDashboard access control - throws exception when AdminServicePrincipal attempts to access GetDashboard', function () {
    // Create connection with AdminServicePrincipal account type
    $powerBIConnection = new PowerBIServicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-admin-client-id',
        clientSecret: 'test-admin-client-secret',
        connectionAccountType: ConnectionAccountType::AdminServicePrincipal
    );

    // No need to authenticate - the restriction check happens before the HTTP request
    // Attempt to send the request - should throw before making API call
    $request = new GetDashboard('test-dashboard-id');

    expect(fn () => $powerBIConnection->send($request))
        ->toThrow(AccountTypeRestrictedException::class, "Account type 'AdminServicePrincipal' cannot access GET /dashboards/test-dashboard-id");
});
