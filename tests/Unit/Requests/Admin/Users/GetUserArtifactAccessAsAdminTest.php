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
