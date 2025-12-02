<?php

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
