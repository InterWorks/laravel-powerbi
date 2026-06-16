<?php

use Illuminate\Support\Collection;
use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\DTO\ScanDataset;
use InterWorks\PowerBI\DTO\ScanRequest;
use InterWorks\PowerBI\DTO\ScanResult;
use InterWorks\PowerBI\DTO\ScanWorkspace;
use InterWorks\PowerBI\Requests\Admin\Workspaces\GetScanResult;
use InterWorks\PowerBI\Requests\Admin\Workspaces\GetScanStatus;
use InterWorks\PowerBI\Requests\Admin\Workspaces\PostWorkspaceInfo;
use InterWorks\PowerBI\Tests\Fixtures\PowerBIFixture;
use Saloon\Http\Faking\MockClient;

//
// PostWorkspaceInfo
//

test('PostWorkspaceInfo body contains workspace IDs', function () {
    $request = new PostWorkspaceInfo(['ws-1', 'ws-2']);

    $method = new ReflectionMethod($request, 'defaultBody');
    $method->setAccessible(true);
    $body = $method->invoke($request);

    expect($body)->toBe(['workspaces' => ['ws-1', 'ws-2']]);
});

test('PostWorkspaceInfo default query params', function () {
    $request = new PostWorkspaceInfo(['ws-1']);
    $query = $request->query()->all();

    expect($query['datasetSchema'])->toBe('true')
        ->and($query['datasetExpressions'])->toBe('false')
        ->and($query['lineage'])->toBe('false')
        ->and($query['datasourceDetails'])->toBe('false')
        ->and($query['getArtifactUsers'])->toBe('false');
});

test('PostWorkspaceInfo query params reflect constructor args', function () {
    $request = new PostWorkspaceInfo(
        workspaceIds: ['ws-1'],
        datasetSchema: false,
        datasetExpressions: true,
        lineage: true,
        datasourceDetails: true,
        getArtifactUsers: true,
    );
    $query = $request->query()->all();

    expect($query['datasetSchema'])->toBe('false')
        ->and($query['datasetExpressions'])->toBe('true')
        ->and($query['lineage'])->toBe('true')
        ->and($query['datasourceDetails'])->toBe('true')
        ->and($query['getArtifactUsers'])->toBe('true');
});

test('PostWorkspaceInfo throws when workspace IDs list is empty', function () {
    new PostWorkspaceInfo([]);
})->throws(InvalidArgumentException::class, 'between 1 and 100');

test('PostWorkspaceInfo throws when workspace IDs exceed 100', function () {
    new PostWorkspaceInfo(array_fill(0, 101, 'ws-id'));
})->throws(InvalidArgumentException::class, 'between 1 and 100');

test('PostWorkspaceInfo accepts boundary of 1 workspace ID', function () {
    $request = new PostWorkspaceInfo(['ws-1']);
    expect($request)->toBeInstanceOf(PostWorkspaceInfo::class);
});

test('PostWorkspaceInfo accepts boundary of 100 workspace IDs', function () {
    $request = new PostWorkspaceInfo(array_fill(0, 100, 'ws-id'));
    expect($request)->toBeInstanceOf(PostWorkspaceInfo::class);
});

test('PostWorkspaceInfo response maps to ScanRequest DTO', function () {
    $mockClient = new MockClient([
        PostWorkspaceInfo::class => new PowerBIFixture('admin/workspaces/post-workspace-info'),
    ]);

    $connector = new PowerBIServicePrincipal;
    $response = $connector->send(new PostWorkspaceInfo(['ws-1']), mockClient: $mockClient);

    expect($response->status())->toBe(202);
    $dto = $response->dto();
    expect($dto)->toBeInstanceOf(ScanRequest::class)
        ->and($dto->id)->toBeString()
        ->and($dto->status)->toBe('NotStarted')
        ->and($dto->createdDateTime)->toBeString();
});

//
// GetScanStatus
//

test('GetScanStatus endpoint includes scan ID', function () {
    $request = new GetScanStatus('my-scan-id');
    expect($request->resolveEndpoint())->toBe('/admin/workspaces/scanStatus/my-scan-id');
});

test('GetScanStatus response maps to ScanRequest DTO', function () {
    $mockClient = new MockClient([
        GetScanStatus::class => new PowerBIFixture('admin/workspaces/get-scan-status'),
    ]);

    $connector = new PowerBIServicePrincipal;
    $response = $connector->send(new GetScanStatus('a1b2c3d4'), mockClient: $mockClient);

    expect($response->status())->toBe(200);
    $dto = $response->dto();
    expect($dto)->toBeInstanceOf(ScanRequest::class)
        ->and($dto->id)->toBeString()
        ->and($dto->status)->toBe('Succeeded')
        ->and($dto->isSucceeded())->toBeTrue()
        ->and($dto->isComplete())->toBeTrue();
});

test('ScanRequest isSucceeded returns false when not succeeded', function () {
    $dto = ScanRequest::fromArray(['id' => 'scan-id', 'status' => 'Running']);
    expect($dto->isSucceeded())->toBeFalse()
        ->and($dto->isComplete())->toBeFalse();
});

test('ScanRequest isComplete returns true for failed status', function () {
    $dto = ScanRequest::fromArray(['id' => 'scan-id', 'status' => 'Failed']);
    expect($dto->isSucceeded())->toBeFalse()
        ->and($dto->isComplete())->toBeTrue();
});

//
// GetScanResult
//

test('GetScanResult endpoint includes scan ID', function () {
    $request = new GetScanResult('my-scan-id');
    expect($request->resolveEndpoint())->toBe('/admin/workspaces/scanResult/my-scan-id');
});

test('GetScanResult response maps to nested DTOs', function () {
    $mockClient = new MockClient([
        GetScanResult::class => new PowerBIFixture('admin/workspaces/get-scan-result'),
    ]);

    $connector = new PowerBIServicePrincipal;
    $response = $connector->send(new GetScanResult('a1b2c3d4'), mockClient: $mockClient);

    expect($response->status())->toBe(200);
    $result = $response->dto();
    expect($result)->toBeInstanceOf(ScanResult::class)
        ->and($result->workspaces)->toBeInstanceOf(Collection::class)
        ->and($result->workspaces)->toHaveCount(2);

    $workspace = $result->workspaces->first();
    expect($workspace)->toBeInstanceOf(ScanWorkspace::class)
        ->and($workspace->id)->toBeString()
        ->and($workspace->name)->toBe('Sales Analytics')
        ->and($workspace->type)->toBe('Workspace')
        ->and($workspace->state)->toBe('Active')
        ->and($workspace->datasets)->toBeInstanceOf(Collection::class)
        ->and($workspace->datasets)->toHaveCount(1);

    $dataset = $workspace->datasets->first();
    expect($dataset)->toBeInstanceOf(ScanDataset::class)
        ->and($dataset->id)->toBeString()
        ->and($dataset->name)->toBe('Sales Dataset')
        ->and($dataset->configuredBy)->toBe('analyst@example.com')
        ->and($dataset->roles)->toHaveCount(1);

    $role = $dataset->roles->first();
    expect($role->name)->toBe('SalesRegion')
        ->and($role->modelPermission)->toBe('Read')
        ->and($role->tablePermissions)->toHaveCount(1);
});

test('GetScanResult workspace with no datasets returns empty collection', function () {
    $mockClient = new MockClient([
        GetScanResult::class => new PowerBIFixture('admin/workspaces/get-scan-result'),
    ]);

    $connector = new PowerBIServicePrincipal;
    $response = $connector->send(new GetScanResult('a1b2c3d4'), mockClient: $mockClient);

    $financeWorkspace = $response->dto()->workspaces->last();
    expect($financeWorkspace->name)->toBe('Finance Workspace')
        ->and($financeWorkspace->datasets)->toHaveCount(0);
});
