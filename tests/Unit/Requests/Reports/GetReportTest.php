<?php

use InterWorks\PowerBI\Connectors\PowerBIAzureUser;
use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\DTO\Report;
use InterWorks\PowerBI\Enums\ConnectionAccountType;
use InterWorks\PowerBI\Exceptions\AccountTypeRestrictedException;
use InterWorks\PowerBI\PowerBI;
use InterWorks\PowerBI\Requests\Reports\GetReport;
use InterWorks\PowerBI\Tests\Fixtures\PowerBIFixture;
use Saloon\Http\Faking\MockClient;

test('can get single report', function () {
    $mockClient = new MockClient([
        GetReport::class => new PowerBIFixture('reports/get-report'),
    ]);

    // Create the PowerBI connection with AdminServicePrinciple (has access to GetReport)
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
    $request = new GetReport(env('POWER_BI_REPORT_ID'));
    $response = $powerBIConnection->send($request, mockClient: $mockClient);

    // Validate the response
    expect($response->status())->toBe(200);
    expect($response->dto())->toBeInstanceOf(Report::class);
    $report = $response->dto();
    expect($report->datasetId)->toBeString();
    expect($report->id)->toBeString();
    expect($report->name)->toBeString();
    expect($report->webUrl)->toBeString();
    expect($report->embedUrl)->toBeString();
});

test('GetReport access control - allows AzureUser to access GetReport', function () {
    $mockClient = new MockClient([
        GetReport::class => new PowerBIFixture('reports/get-report'),
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
    $request = new GetReport(env('POWER_BI_REPORT_ID'));

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

test('GetReport access control - throws exception when ServicePrinciple attempts to access GetReport', function () {
    // Create connection with ServicePrinciple account type
    $powerBIConnection = new PowerBIServicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        connectionAccountType: ConnectionAccountType::ServicePrinciple
    );

    // No need to authenticate - the restriction check happens before the HTTP request
    // Attempt to send the request - should throw before making API call
    $request = new GetReport('test-report-id');

    expect(fn () => $powerBIConnection->send($request))
        ->toThrow(AccountTypeRestrictedException::class, "Account type 'ServicePrinciple' cannot access GET /reports/test-report-id");
});

test('GetReport access control - throws exception when AdminServicePrinciple attempts to access GetReport', function () {
    // Create connection with AdminServicePrinciple account type
    $powerBIConnection = new PowerBIServicePrincipal(
        tenant: 'test-tenant',
        clientId: 'test-admin-client-id',
        clientSecret: 'test-admin-client-secret',
        connectionAccountType: ConnectionAccountType::AdminServicePrinciple
    );

    // No need to authenticate - the restriction check happens before the HTTP request
    // Attempt to send the request - should throw before making API call
    $request = new GetReport('test-report-id');

    expect(fn () => $powerBIConnection->send($request))
        ->toThrow(AccountTypeRestrictedException::class, "Account type 'AdminServicePrinciple' cannot access GET /reports/test-report-id");
});
