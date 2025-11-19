<?php

use Carbon\Carbon;
use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\DTO\EmbedToken;
use InterWorks\PowerBI\PowerBI;
use InterWorks\PowerBI\Requests\EmbedToken\ReportsGenerateTokenInGroup;
use InterWorks\PowerBI\Tests\Fixtures\PowerBIFixture;
use Saloon\Http\Faking\MockClient;

test('can get an embed token for a report from a specified group', function () {
    $mockClient = new MockClient([
        ReportsGenerateTokenInGroup::class => new PowerBIFixture('embed-token/reports-generate-token-in-group'),
    ]);

    // Create the PowerBI connection and authenticate
    $powerBIConnection = new PowerBIServicePrincipal;
    $authenticator = $powerBIConnection->getAccessToken();
    $powerBIConnection->authenticate($authenticator);

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
