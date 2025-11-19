<?php

use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\DTO\Dashboard;
use InterWorks\PowerBI\DTO\Dashboards;
use InterWorks\PowerBI\PowerBI;
use InterWorks\PowerBI\Requests\Dashboards\GetDashboardsInGroup;
use InterWorks\PowerBI\Tests\Fixtures\PowerBIFixture;
use Saloon\Http\Faking\MockClient;

test('can get dashboards in group', function () {
    $mockClient = new MockClient([
        GetDashboardsInGroup::class => new PowerBIFixture('dashboards/get-dashboards-in-group'),
    ]);

    // Create the PowerBI connection and authenticate
    $powerBIConnection = new PowerBIServicePrincipal;
    $authenticator = $powerBIConnection->getAccessToken();
    $powerBIConnection->authenticate($authenticator);

    // Send the request
    $request = new GetDashboardsInGroup(env('POWER_BI_GROUP_ID'));
    $response = $powerBIConnection->send($request, mockClient: $mockClient);

    // Validate the response
    expect($response->status())->toBe(200);
    expect($response->dto())->toBeInstanceOf(Dashboards::class);
    foreach ($response->dto()->dashboards as $dashboard) {
        expect($dashboard)->toBeInstanceOf(Dashboard::class);
        expect($dashboard->id)->toBeString();
        expect($dashboard->displayName)->toBeString();
        expect($dashboard->isReadOnly)->toBeBool();
        expect($dashboard->embedUrl)->toBeString();
    }
});
