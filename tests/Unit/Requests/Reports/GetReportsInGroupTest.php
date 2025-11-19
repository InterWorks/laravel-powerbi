<?php

use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\DTO\Report;
use InterWorks\PowerBI\DTO\Reports;
use InterWorks\PowerBI\PowerBI;
use InterWorks\PowerBI\Requests\Reports\GetReportsInGroup;
use InterWorks\PowerBI\Tests\Fixtures\PowerBIFixture;
use Saloon\Http\Faking\MockClient;

test('can get reports in a specified group', function () {
    $mockClient = new MockClient([
        GetReportsInGroup::class => new PowerBIFixture('reports/get-reports-in-group'),
    ]);

    // Create the PowerBI connection and authenticate
    $powerBIConnection = new PowerBIServicePrincipal;
    $authenticator = $powerBIConnection->getAccessToken();
    $powerBIConnection->authenticate($authenticator);

    // Send the request
    $request = new GetReportsInGroup(env('POWER_BI_GROUP_ID'));
    $response = $powerBIConnection->send($request, mockClient: $mockClient);

    // Validate the response
    expect($response->status())->toBe(200);
    expect($response->dto())->toBeInstanceOf(Reports::class);
    foreach ($response->dto()->reports as $report) {
        expect($report)->toBeInstanceOf(Report::class);
        expect($report->datasetId)->toBeString();
        expect($report->id)->toBeString();
        expect($report->name)->toBeString();
        expect($report->webUrl)->toBeString();
        expect($report->embedUrl)->toBeString();
    }
});
