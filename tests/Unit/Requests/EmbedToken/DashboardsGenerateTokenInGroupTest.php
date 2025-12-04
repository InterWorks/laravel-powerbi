<?php

use Carbon\Carbon;
use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\DTO\EmbedToken;
use InterWorks\PowerBI\Requests\EmbedToken\DashboardsGenerateTokenInGroup;
use InterWorks\PowerBI\Tests\Fixtures\PowerBIFixture;
use Saloon\Http\Faking\MockClient;

test('can get an embed token for a dashboard from a specified group', function () {
    $mockClient = new MockClient([
        DashboardsGenerateTokenInGroup::class => new PowerBIFixture('embed-token/dashboards-generate-token-in-group'),
    ]);

    // Create the Service Principal connection
    $powerBIConnection = new PowerBIServicePrincipal;

    // Token authentication only needed when recording responses
    // $authenticator = $powerBIConnection->getAccessToken();
    // $powerBIConnection->authenticate($authenticator);

    // Send the request
    $request = new DashboardsGenerateTokenInGroup(env('POWER_BI_GROUP_ID'), env('POWER_BI_DASHBOARD_ID'));
    $response = $powerBIConnection->send($request, mockClient: $mockClient);

    // Validate the response
    expect($response->status())->toBe(200);
    expect($response->dto())->toBeInstanceOf(EmbedToken::class);
    $embedToken = $response->dto();
    expect($embedToken->token)->toBeString();
    expect($embedToken->tokenId)->toBeString();
    expect($embedToken->expiration)->toBeInstanceOf(Carbon::class);

    // The expiration should be one hour from now
    Carbon::setTestNow('2025-11-18 15:52:00'); // Mock the time to align with the saved fixture
    $oneHourFromNow = Carbon::now()->addHour();
    expect($embedToken->expiration->lessThanOrEqualTo($oneHourFromNow))->toBeTrue();
    expect($embedToken->expiration->greaterThan(Carbon::now()->addMinutes(55)))->toBeTrue();
});
