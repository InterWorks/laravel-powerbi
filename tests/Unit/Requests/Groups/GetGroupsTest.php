<?php

use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\DTO\Group;
use InterWorks\PowerBI\DTO\Groups;
use InterWorks\PowerBI\PowerBI;
use InterWorks\PowerBI\Requests\Groups\GetGroups;
use InterWorks\PowerBI\Tests\Fixtures\PowerBIFixture;
use Saloon\Http\Faking\MockClient;

test('can get groups', function () {
    $mockClient = new MockClient([
        GetGroups::class => new PowerBIFixture('groups/get-groups'),
    ]);

    // Create the PowerBI connection and authenticate
    $powerBIConnection = new PowerBIServicePrincipal;
    $authenticator = $powerBIConnection->getAccessToken();
    $powerBIConnection->authenticate($authenticator);

    // Send the request
    $request = new GetGroups;
    $response = $powerBIConnection->send($request, mockClient: $mockClient);

    // Validate the response
    expect($response->status())->toBe(200);
    expect($response->dto())->toBeInstanceOf(Groups::class);
    foreach ($response->dto()->groups as $group) {
        expect($group)->toBeInstanceOf(Group::class);
        expect($group->id)->toBeString();
        expect($group->isReadOnly)->toBeBool();
        expect($group->isOnDedicatedCapacity)->toBeBool();
        expect($group->type)->toBeString();
        expect($group->name)->toBeString();
    }
});
