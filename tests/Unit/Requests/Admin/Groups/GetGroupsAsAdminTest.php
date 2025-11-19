<?php

use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\DTO\Group;
use InterWorks\PowerBI\DTO\Groups;
use InterWorks\PowerBI\Exceptions\UnauthorizedAdminAccessException;
use InterWorks\PowerBI\Requests\Admin\Groups\GetGroupsAsAdmin;
use InterWorks\PowerBI\Tests\Fixtures\PowerBIFixture;
use Saloon\Http\Faking\MockClient;

test('has default top query parameter', function () {
    $request = new GetGroupsAsAdmin;
    $query = $request->query()->all();
    expect($query)->toHaveKey('$top', 1000);
});

test('can get groups as admin', function () {
    $mockClient = new MockClient([
        GetGroupsAsAdmin::class => new PowerBIFixture('admin/groups/get-groups-as-admin'),
    ]);

    $powerBIConnection = new PowerBIServicePrincipal(
        env('POWER_BI_TENANT'),
        env('POWER_BI_CLIENT_ID'),
        env('POWER_BI_CLIENT_SECRET')
    );
    $authenticator = $powerBIConnection->getAccessToken();
    $powerBIConnection->authenticate($authenticator);
    $request = new GetGroupsAsAdmin;
    $response = $powerBIConnection->send($request, mockClient: $mockClient);
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

// Boundary testing for $top parameter
test('throws exception when top is too high', function () {
    new GetGroupsAsAdmin(top: 5001);
})->throws(InvalidArgumentException::class, 'The $top parameter must be between 1 and 5000.');

test('throws exception when top is zero', function () {
    new GetGroupsAsAdmin(top: 0);
})->throws(InvalidArgumentException::class, 'The $top parameter must be between 1 and 5000.');

test('throws exception when top is negative', function () {
    new GetGroupsAsAdmin(top: -1);
})->throws(InvalidArgumentException::class, 'The $top parameter must be between 1 and 5000.');

test('accepts top at minimum boundary', function () {
    $request = new GetGroupsAsAdmin(top: 1);
    expect($request->query()->all())->toHaveKey('$top', 1);
});

test('accepts top at maximum boundary', function () {
    $request = new GetGroupsAsAdmin(top: 5000);
    expect($request->query()->all())->toHaveKey('$top', 5000);
});

// Expand parameter tests
test('throws exception for invalid expand option', function () {
    $request = new GetGroupsAsAdmin(expand: ['invalid']);
    $request->query()->all(); // This triggers the validation in getExpand()
})->throws(InvalidArgumentException::class, 'Invalid expand option: invalid');

test('accepts valid expand options', function () {
    $request = new GetGroupsAsAdmin(expand: ['users', 'reports']);
    expect($request->query()->all())->toHaveKey('$expand', 'users,reports');
});

test('formats expand array as comma-separated string', function () {
    $request = new GetGroupsAsAdmin(expand: ['users', 'reports', 'dashboards']);
    expect($request->query()->all()['$expand'])->toBe('users,reports,dashboards');
});

test('does not include expand parameter when array is empty', function () {
    $request = new GetGroupsAsAdmin(expand: []);
    expect($request->query()->all())->not->toHaveKey('$expand');
});

test('accepts all valid expand options', function () {
    $request = new GetGroupsAsAdmin(
        expand: ['users', 'reports', 'dashboards', 'datasets', 'dataflows', 'workbooks']
    );
    $query = $request->query()->all();
    expect($query['$expand'])->toBe('users,reports,dashboards,datasets,dataflows,workbooks');
});

// Optional parameter tests
test('includes skip parameter when provided', function () {
    $request = new GetGroupsAsAdmin(skip: 100);
    expect($request->query()->all())->toHaveKey('$skip', 100);
});

test('does not include skip parameter when null', function () {
    $request = new GetGroupsAsAdmin(skip: null);
    expect($request->query()->all())->not->toHaveKey('$skip');
});

test('includes filter parameter when provided', function () {
    $request = new GetGroupsAsAdmin(filter: "type eq 'Workspace'");
    expect($request->query()->all())->toHaveKey('$filter', "type eq 'Workspace'");
});

test('does not include filter parameter when null', function () {
    $request = new GetGroupsAsAdmin(filter: null);
    expect($request->query()->all())->not->toHaveKey('$filter');
});

test('includes all optional parameters when provided', function () {
    $request = new GetGroupsAsAdmin(
        top: 500,
        expand: ['users', 'reports'],
        filter: "type eq 'Workspace'",
        skip: 50
    );
    $query = $request->query()->all();
    expect($query)->toHaveKey('$top', 500);
    expect($query)->toHaveKey('$expand', 'users,reports');
    expect($query)->toHaveKey('$filter', "type eq 'Workspace'");
    expect($query)->toHaveKey('$skip', 50);
});

// Error handling tests
test('throws UnauthorizedAdminAccessException when non-admin tries to access admin endpoint', function () {
    $mockClient = new MockClient([
        GetGroupsAsAdmin::class => new PowerBIFixture('admin/groups/get-groups-as-admin-401'),
    ]);

    $powerBIConnection = new PowerBIServicePrincipal(
        env('POWER_BI_TENANT'),
        env('POWER_BI_CLIENT_ID'),
        env('POWER_BI_CLIENT_SECRET')
    );
    $authenticator = $powerBIConnection->getAccessToken();
    $powerBIConnection->authenticate($authenticator);
    $request = new GetGroupsAsAdmin;

    $powerBIConnection->send($request, mockClient: $mockClient);
})->throws(
    UnauthorizedAdminAccessException::class,
    "Unauthorized access to Power BI Admin endpoint '/admin/groups' (HTTP 401)"
);
