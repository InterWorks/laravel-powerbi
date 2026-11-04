<?php

use Carbon\Carbon;
use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\DTO\EmbedToken;
use InterWorks\PowerBI\Requests\EmbedToken\ReportsGenerateTokenInGroup;
use InterWorks\PowerBI\Tests\Fixtures\PowerBIFixture;
use Saloon\Http\Faking\MockClient;

test('body contains only accessLevel when no identities provided', function () {
    $request = new ReportsGenerateTokenInGroup('group-id', 'report-id');

    $method = new ReflectionMethod($request, 'defaultBody');
    $method->setAccessible(true);
    $body = $method->invoke($request);

    expect($body)->toBe(['accessLevel' => 'View'])
        ->and($body)->not->toHaveKey('identities');
});

test('body includes identities when provided', function () {
    $identities = [
        [
            'username' => 'user@example.com',
            'roles' => ['SalesRegion'],
            'datasets' => ['dataset-id-1'],
        ],
    ];

    $request = new ReportsGenerateTokenInGroup('group-id', 'report-id', 'View', $identities);

    $method = new ReflectionMethod($request, 'defaultBody');
    $method->setAccessible(true);
    $body = $method->invoke($request);

    expect($body['accessLevel'])->toBe('View')
        ->and($body['identities'])->toBe($identities);
});

test('body supports multiple identities for multi-role RLS', function () {
    $identities = [
        [
            'username' => 'user-a@example.com',
            'roles' => ['RoleA'],
            'datasets' => ['dataset-1'],
        ],
        [
            'username' => 'user-b@example.com',
            'roles' => ['RoleB', 'RoleC'],
            'datasets' => ['dataset-1', 'dataset-2'],
        ],
    ];

    $request = new ReportsGenerateTokenInGroup('group-id', 'report-id', 'View', $identities);

    $method = new ReflectionMethod($request, 'defaultBody');
    $method->setAccessible(true);
    $body = $method->invoke($request);

    expect($body['identities'])->toHaveCount(2)
        ->and($body['identities'][1]['roles'])->toBe(['RoleB', 'RoleC']);
});

test('accessLevel can be overridden', function () {
    $request = new ReportsGenerateTokenInGroup('group-id', 'report-id', 'Edit');

    $method = new ReflectionMethod($request, 'defaultBody');
    $method->setAccessible(true);
    $body = $method->invoke($request);

    expect($body['accessLevel'])->toBe('Edit');
});

test('can get an embed token for a report from a specified group', function () {
    $mockClient = new MockClient([
        ReportsGenerateTokenInGroup::class => new PowerBIFixture('embed-token/reports-generate-token-in-group'),
    ]);

    // Create the Service Principal connection
    $powerBIConnection = new PowerBIServicePrincipal;

    // Token authentication only needed when recording responses
    // $authenticator = $powerBIConnection->getAccessToken();
    // $powerBIConnection->authenticate($authenticator);

    // Send the request
    $request = new ReportsGenerateTokenInGroup(env('POWER_BI_GROUP_ID'), env('POWER_BI_REPORT_ID'));
    $response = $powerBIConnection->send($request, mockClient: $mockClient);

    // Validate the response
    expect($response->status())->toBe(200);
    expect($response->dto())->toBeInstanceOf(EmbedToken::class);
    $embedToken = $response->dto();
    expect($embedToken->token)->toBeString();
    expect($embedToken->tokenId)->toBeString();
    expect($embedToken->expiration)->toBeInstanceOf(Carbon::class);

    // The expiration should be one hour from now
    Carbon::setTestNow('2025-11-18 00:20:56'); // Mock the time to align with the saved fixture
    $oneHourFromNow = Carbon::now()->addHour();
    expect($embedToken->expiration->lessThanOrEqualTo($oneHourFromNow))->toBeTrue();
    expect($embedToken->expiration->greaterThan(Carbon::now()->addMinutes(55)))->toBeTrue();
});
