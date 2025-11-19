<?php

use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\DTO\Report;
use InterWorks\PowerBI\PowerBI;
use InterWorks\PowerBI\Requests\Reports\GetReportInGroup;
use InterWorks\PowerBI\Tests\Fixtures\PowerBIFixture;
use Saloon\Http\Faking\MockClient;

test('can get single report from a specified group', function () {
    $mockClient = new MockClient([
        GetReportInGroup::class => new PowerBIFixture('reports/get-report-in-group'),
    ]);

    // Create the PowerBI connection and authenticate
    $powerBIConnection = new PowerBIServicePrincipal;
    $authenticator = $powerBIConnection->getAccessToken();
    $powerBIConnection->authenticate($authenticator);

    // Send the request
    $request = new GetReportInGroup(env('POWER_BI_GROUP_ID'), env('POWER_BI_REPORT_ID'));
    $response = $powerBIConnection->send($request, mockClient: $mockClient);

    // Validate the response
    expect($response->status())->toBe(200);
    expect($response->dto())->toBeInstanceOf(Report::class);
    $report = $response->dto();
    expect($report->datasetId)->toBeString();
    expect($report->id)->toBeString();
    expect($report->name)->toBeString();
    expect($report->webUrl)->toBeString();
    expect($report->embedUrl)->toBeString();
});
