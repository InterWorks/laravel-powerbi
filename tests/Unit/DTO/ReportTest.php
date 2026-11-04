<?php

use InterWorks\PowerBI\DTO\Report;
use InterWorks\PowerBI\Enums\ReportType;

test('maps every field from an API item', function () {
    $report = Report::fromItem([
        'id' => 'report-id',
        'appId' => 'app-id',
        'name' => 'Sales',
        'description' => 'Sales by region',
        'isOwnedByMe' => true,
        'reportType' => 'PaginatedReport',
        'datasetId' => 'dataset-id',
        'datasetWorkspaceId' => 'workspace-id',
        'webUrl' => 'https://app.powerbi.com/report',
        'embedUrl' => 'https://app.powerbi.com/embed',
        'users' => [['identifier' => 'user']],
        'subscriptions' => [['id' => 'subscription']],
    ]);

    expect($report->id)->toBe('report-id')
        ->and($report->appId)->toBe('app-id')
        ->and($report->name)->toBe('Sales')
        ->and($report->description)->toBe('Sales by region')
        ->and($report->isOwnedByMe)->toBeTrue()
        ->and($report->reportType)->toBe(ReportType::PaginatedReport)
        ->and($report->datasetId)->toBe('dataset-id')
        ->and($report->datasetWorkspaceId)->toBe('workspace-id')
        ->and($report->webUrl)->toBe('https://app.powerbi.com/report')
        ->and($report->embedUrl)->toBe('https://app.powerbi.com/embed')
        ->and($report->users)->toBe([['identifier' => 'user']])
        ->and($report->subscriptions)->toBe([['id' => 'subscription']]);
});

test('optional fields are null when the API omits them', function () {
    $report = Report::fromItem([
        'id' => 'report-id',
        'name' => 'Sales',
        'isOwnedByMe' => false,
        'reportType' => 'PowerBIReport',
        'webUrl' => 'https://app.powerbi.com/report',
        'embedUrl' => 'https://app.powerbi.com/embed',
        'users' => [],
        'subscriptions' => [],
    ]);

    expect($report->appId)->toBeNull()
        ->and($report->description)->toBeNull()
        ->and($report->datasetId)->toBeNull()
        ->and($report->datasetWorkspaceId)->toBeNull();
});
