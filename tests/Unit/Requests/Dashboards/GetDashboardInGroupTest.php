<?php

use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\DTO\Dashboard;
use InterWorks\PowerBI\Requests\Dashboards\GetDashboardInGroup;
use InterWorks\PowerBI\Tests\Fixtures\PowerBIFixture;
use Saloon\Http\Faking\MockClient;

test('can get single dashboard from a specified group', function () {
    $mockClient = new MockClient([
        GetDashboardInGroup::class => new PowerBIFixture('dashboards/get-dashboard-in-group'),
    ]);

    $powerBIConnection = new PowerBIServicePrincipal;
    $authenticator = $powerBIConnection->getAccessToken();
    $powerBIConnection->authenticate($authenticator);
    $request = new GetDashboardInGroup((env('POWER_BI_GROUP_ID')), (env('POWER_BI_DASHBOARD_ID')));
    $response = $powerBIConnection->send($request, mockClient: $mockClient);
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
