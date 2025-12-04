<?php

use Illuminate\Support\Collection;
use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\DTO\ArtifactAccessEntry;
use InterWorks\PowerBI\DTO\ArtifactAccessResponse;
use InterWorks\PowerBI\DTO\User;
use InterWorks\PowerBI\Enums\ArtifactType;
use InterWorks\PowerBI\Requests\Admin\Users\GetUserArtifactAccessAsAdmin;
use InterWorks\PowerBI\Tests\Fixtures\PowerBIFixture;
use Saloon\Http\Faking\MockClient;

test('can get user artifact access as admin', function () {
    $mockClient = new MockClient([
        GetUserArtifactAccessAsAdmin::class => new PowerBIFixture('admin/users/get-user-artifact-access-as-admin'),
    ]);

    $powerBIConnection = new PowerBIServicePrincipal(
        env('POWER_BI_TENANT'),
        env('POWER_BI_CLIENT_ID'),
        env('POWER_BI_CLIENT_SECRET')
    );
    $authenticator = $powerBIConnection->getAccessToken();
    $powerBIConnection->authenticate($authenticator);
    $request = new GetUserArtifactAccessAsAdmin(userId: 'test-user@example.com');
    $response = $powerBIConnection->send($request, mockClient: $mockClient);

    expect($response->status())->toBe(200);
    expect($response->dto())->toBeInstanceOf(ArtifactAccessResponse::class);
});

test('response includes continuation token when available', function () {
    $mockClient = new MockClient([
        GetUserArtifactAccessAsAdmin::class => new PowerBIFixture('admin/users/get-user-artifact-access-as-admin'),
    ]);

    $powerBIConnection = new PowerBIServicePrincipal(
        env('POWER_BI_TENANT'),
        env('POWER_BI_CLIENT_ID'),
        env('POWER_BI_CLIENT_SECRET')
    );
    $authenticator = $powerBIConnection->getAccessToken();
    $powerBIConnection->authenticate($authenticator);
    $request = new GetUserArtifactAccessAsAdmin(userId: 'test-user@example.com');
    $response = $powerBIConnection->send($request, mockClient: $mockClient);

    $dto = $response->dto();
    expect($dto->continuationToken)->toBeString();
    expect($dto->continuationToken)->toBe('next-page-token-example');
    expect($dto->continuationUri)->toBeString();
    expect($dto->continuationUri)->toContain('continuationToken=next-page-token-example');
});

test('artifact access entries have correct structure', function () {
    $mockClient = new MockClient([
        GetUserArtifactAccessAsAdmin::class => new PowerBIFixture('admin/users/get-user-artifact-access-as-admin'),
    ]);

    $powerBIConnection = new PowerBIServicePrincipal(
        env('POWER_BI_TENANT'),
        env('POWER_BI_CLIENT_ID'),
        env('POWER_BI_CLIENT_SECRET')
    );
    $authenticator = $powerBIConnection->getAccessToken();
    $powerBIConnection->authenticate($authenticator);
    $request = new GetUserArtifactAccessAsAdmin(userId: 'test-user@example.com');
    $response = $powerBIConnection->send($request, mockClient: $mockClient);

    $dto = $response->dto();
    expect($dto->artifactAccessEntities)->toBeInstanceOf(Collection::class);
    expect($dto->artifactAccessEntities)->toHaveCount(5);

    foreach ($dto->artifactAccessEntities as $entry) {
        expect($entry)->toBeInstanceOf(ArtifactAccessEntry::class);
        expect($entry->artifactId)->toBeString();
        expect($entry->displayName)->toBeString();
        expect($entry->artifactType)->toBeInstanceOf(ArtifactType::class);
        expect($entry->accessRight)->toBeString();
    }
});

test('artifact access entries include sharer when available', function () {
    $mockClient = new MockClient([
        GetUserArtifactAccessAsAdmin::class => new PowerBIFixture('admin/users/get-user-artifact-access-as-admin'),
    ]);

    $powerBIConnection = new PowerBIServicePrincipal(
        env('POWER_BI_TENANT'),
        env('POWER_BI_CLIENT_ID'),
        env('POWER_BI_CLIENT_SECRET')
    );
    $authenticator = $powerBIConnection->getAccessToken();
    $powerBIConnection->authenticate($authenticator);
    $request = new GetUserArtifactAccessAsAdmin(userId: 'test-user@example.com');
    $response = $powerBIConnection->send($request, mockClient: $mockClient);

    $dto = $response->dto();
    $firstEntry = $dto->artifactAccessEntities->first();

    expect($firstEntry->sharer)->toBeInstanceOf(User::class);
    expect($firstEntry->sharer->emailAddress)->toBe('admin@example.com');
    expect($firstEntry->sharer->displayName)->toBe('Admin User');
    expect($firstEntry->sharer->identifier)->toBeString();
    expect($firstEntry->shareType)->toBeString();
});

test('artifact access entries handle missing sharer field', function () {
    $mockClient = new MockClient([
        GetUserArtifactAccessAsAdmin::class => new PowerBIFixture('admin/users/get-user-artifact-access-as-admin'),
    ]);

    $powerBIConnection = new PowerBIServicePrincipal(
        env('POWER_BI_TENANT'),
        env('POWER_BI_CLIENT_ID'),
        env('POWER_BI_CLIENT_SECRET')
    );
    $authenticator = $powerBIConnection->getAccessToken();
    $powerBIConnection->authenticate($authenticator);
    $request = new GetUserArtifactAccessAsAdmin(userId: 'test-user@example.com');
    $response = $powerBIConnection->send($request, mockClient: $mockClient);

    $dto = $response->dto();
    // Third entry in fixture has no sharer
    $entryWithoutSharer = $dto->artifactAccessEntities->get(2);

    expect($entryWithoutSharer->sharer)->toBeNull();
    expect($entryWithoutSharer->shareType)->toBeNull();
});

test('includes artifact types parameter when provided', function () {
    $request = new GetUserArtifactAccessAsAdmin(
        userId: 'test-user@example.com',
        artifactTypes: [ArtifactType::Report, ArtifactType::Dashboard]
    );

    $query = $request->query()->all();
    expect($query)->toHaveKey('artifactTypes', 'Report,Dashboard');
});

test('does not include artifact types parameter when empty', function () {
    $request = new GetUserArtifactAccessAsAdmin(
        userId: 'test-user@example.com',
        artifactTypes: []
    );

    $query = $request->query()->all();
    expect($query)->not->toHaveKey('artifactTypes');
});

test('request includes continuation token parameter when provided', function () {
    $request = new GetUserArtifactAccessAsAdmin(
        userId: 'test-user@example.com',
        continuationToken: 'next-page-token'
    );

    $query = $request->query()->all();
    expect($query)->toHaveKey('continuationToken', 'next-page-token');
});

test('does not include continuation token parameter when null', function () {
    $request = new GetUserArtifactAccessAsAdmin(
        userId: 'test-user@example.com',
        continuationToken: null
    );

    $query = $request->query()->all();
    expect($query)->not->toHaveKey('continuationToken');
});

test('includes all parameters when provided', function () {
    $request = new GetUserArtifactAccessAsAdmin(
        userId: 'test-user@example.com',
        artifactTypes: [ArtifactType::Report, ArtifactType::Dashboard, ArtifactType::Dataset],
        continuationToken: 'next-page-token'
    );

    $query = $request->query()->all();
    expect($query)->toHaveKey('artifactTypes', 'Report,Dashboard,Dataset');
    expect($query)->toHaveKey('continuationToken', 'next-page-token');
});

test('resolves endpoint with user id', function () {
    $request = new GetUserArtifactAccessAsAdmin(userId: 'test-user@example.com');
    expect($request->resolveEndpoint())->toBe('/admin/users/test-user@example.com/artifactAccess');
});

test('accepts all artifact types', function () {
    $request = new GetUserArtifactAccessAsAdmin(
        userId: 'test-user@example.com',
        artifactTypes: [
            ArtifactType::Report,
            ArtifactType::PaginatedReport,
            ArtifactType::Dashboard,
            ArtifactType::Dataset,
            ArtifactType::Dataflow,
            ArtifactType::PersonalGroup,
            ArtifactType::Group,
            ArtifactType::Workspace,
            ArtifactType::Capacity,
            ArtifactType::App,
        ]
    );

    $query = $request->query()->all();
    expect($query['artifactTypes'])->toBe('Report,PaginatedReport,Dashboard,Dataset,Dataflow,PersonalGroup,Group,Workspace,Capacity,App');
});

// Pagination tests
test('getAllPages fetches all results across multiple pages', function () {
    // Create a callable that returns different responses sequentially
    $callCount = 0;
    $mockClient = new MockClient([
        GetUserArtifactAccessAsAdmin::class => function () use (&$callCount) {
            $responses = [
                new PowerBIFixture('admin/users/get-user-artifact-access-as-admin'),      // Page 1: 5 items
                new PowerBIFixture('admin/users/get-user-artifact-access-as-admin-page2'), // Page 2: 3 items
                new PowerBIFixture('admin/users/get-user-artifact-access-as-admin-page3'), // Page 3: 2 items
            ];

            return $responses[$callCount++] ?? $responses[2];
        },
    ]);

    $powerBIConnection = new PowerBIServicePrincipal(
        env('POWER_BI_TENANT'),
        env('POWER_BI_CLIENT_ID'),
        env('POWER_BI_CLIENT_SECRET')
    );
    $authenticator = $powerBIConnection->getAccessToken();
    $powerBIConnection->authenticate($authenticator);

    $request = new GetUserArtifactAccessAsAdmin(
        userId: 'test-user@example.com',
        artifactTypes: [ArtifactType::Report, ArtifactType::Dashboard]
    );

    $allResults = $request->getAllPages($powerBIConnection, $mockClient);

    // Should have 10 total items (5 + 3 + 2)
    expect($allResults)->toBeInstanceOf(Collection::class);
    expect($allResults)->toHaveCount(10);

    // Verify all are ArtifactAccessEntry instances
    foreach ($allResults as $entry) {
        expect($entry)->toBeInstanceOf(ArtifactAccessEntry::class);
    }

    // Verify items from different pages are present
    $displayNames = $allResults->pluck('displayName')->toArray();
    expect($displayNames)->toContain('Sales Dashboard');      // Page 1
    expect($displayNames)->toContain('HR Dashboard');         // Page 2
    expect($displayNames)->toContain('Final Report');         // Page 3
});

test('getAllPages handles single page with no continuation token', function () {
    $mockClient = new MockClient([
        GetUserArtifactAccessAsAdmin::class => new PowerBIFixture('admin/users/get-user-artifact-access-as-admin-page3'),
    ]);

    $powerBIConnection = new PowerBIServicePrincipal(
        env('POWER_BI_TENANT'),
        env('POWER_BI_CLIENT_ID'),
        env('POWER_BI_CLIENT_SECRET')
    );
    $authenticator = $powerBIConnection->getAccessToken();
    $powerBIConnection->authenticate($authenticator);

    $request = new GetUserArtifactAccessAsAdmin(userId: 'test-user@example.com');
    $allResults = $request->getAllPages($powerBIConnection, $mockClient);

    // Page 3 has 2 items and no continuation token
    expect($allResults)->toHaveCount(2);
    expect($allResults->pluck('displayName')->toArray())->toEqual([
        'Final Report',
        'Archive Workspace',
    ]);
});

test('withOnlyContinuationToken creates request without artifact types', function () {
    $originalRequest = new GetUserArtifactAccessAsAdmin(
        userId: 'test-user@example.com',
        artifactTypes: [ArtifactType::Report, ArtifactType::Dashboard],
        continuationToken: null
    );

    // Use reflection to call protected method
    $reflection = new \ReflectionClass($originalRequest);
    $method = $reflection->getMethod('withOnlyContinuationToken');
    $method->setAccessible(true);

    $continuationRequest = $method->invoke($originalRequest, 'next-page-token');

    // Verify the continuation request has no artifact types
    $query = $continuationRequest->query()->all();
    expect($query)->not->toHaveKey('artifactTypes');
    expect($query)->toHaveKey('continuationToken');
    expect($continuationRequest->resolveEndpoint())->toBe('/admin/users/test-user@example.com/artifactAccess');
});

test('extractCollectionFromDto returns correct collection', function () {
    $mockClient = new MockClient([
        GetUserArtifactAccessAsAdmin::class => new PowerBIFixture('admin/users/get-user-artifact-access-as-admin'),
    ]);

    $powerBIConnection = new PowerBIServicePrincipal(
        env('POWER_BI_TENANT'),
        env('POWER_BI_CLIENT_ID'),
        env('POWER_BI_CLIENT_SECRET')
    );
    $authenticator = $powerBIConnection->getAccessToken();
    $powerBIConnection->authenticate($authenticator);

    $request = new GetUserArtifactAccessAsAdmin(userId: 'test-user@example.com');
    $response = $powerBIConnection->send($request, mockClient: $mockClient);
    $dto = $response->dto();

    // Use reflection to test protected method
    $reflection = new \ReflectionClass($request);
    $method = $reflection->getMethod('extractCollectionFromDto');
    $method->setAccessible(true);

    $collection = $method->invoke($request, $dto);

    expect($collection)->toBeInstanceOf(Collection::class);
    expect($collection)->toHaveCount(5);
});

test('extractContinuationToken returns correct token', function () {
    $mockClient = new MockClient([
        GetUserArtifactAccessAsAdmin::class => new PowerBIFixture('admin/users/get-user-artifact-access-as-admin'),
    ]);

    $powerBIConnection = new PowerBIServicePrincipal(
        env('POWER_BI_TENANT'),
        env('POWER_BI_CLIENT_ID'),
        env('POWER_BI_CLIENT_SECRET')
    );
    $authenticator = $powerBIConnection->getAccessToken();
    $powerBIConnection->authenticate($authenticator);

    $request = new GetUserArtifactAccessAsAdmin(userId: 'test-user@example.com');
    $response = $powerBIConnection->send($request, mockClient: $mockClient);
    $dto = $response->dto();

    // Use reflection to test protected method
    $reflection = new \ReflectionClass($request);
    $method = $reflection->getMethod('extractContinuationToken');
    $method->setAccessible(true);

    $token = $method->invoke($request, $dto);

    expect($token)->toBe('next-page-token-example');
});

test('extractContinuationToken returns null when no token present', function () {
    $mockClient = new MockClient([
        GetUserArtifactAccessAsAdmin::class => new PowerBIFixture('admin/users/get-user-artifact-access-as-admin-page3'),
    ]);

    $powerBIConnection = new PowerBIServicePrincipal(
        env('POWER_BI_TENANT'),
        env('POWER_BI_CLIENT_ID'),
        env('POWER_BI_CLIENT_SECRET')
    );
    $authenticator = $powerBIConnection->getAccessToken();
    $powerBIConnection->authenticate($authenticator);

    $request = new GetUserArtifactAccessAsAdmin(userId: 'test-user@example.com');
    $response = $powerBIConnection->send($request, mockClient: $mockClient);
    $dto = $response->dto();

    // Use reflection to test protected method
    $reflection = new \ReflectionClass($request);
    $method = $reflection->getMethod('extractContinuationToken');
    $method->setAccessible(true);

    $token = $method->invoke($request, $dto);

    expect($token)->toBeNull();
});
