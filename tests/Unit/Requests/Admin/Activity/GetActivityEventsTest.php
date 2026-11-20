<?php

use Carbon\Carbon;
use Illuminate\Support\Collection;
use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\DTO\ActivityEvent;
use InterWorks\PowerBI\DTO\ActivityEventsResponse;
use InterWorks\PowerBI\Requests\Admin\Activity\GetActivityEvents;
use InterWorks\PowerBI\Tests\Fixtures\PowerBIFixture;
use Saloon\Http\Faking\MockClient;

//
// Query parameter tests
//

test('query includes startDateTime and endDateTime wrapped in single quotes', function () {
    $request = new GetActivityEvents(
        startDateTime: '2025-11-18T00:00:00',
        endDateTime: '2025-11-18T00:59:59',
    );
    $query = $request->query()->all();

    expect($query['startDateTime'])->toBe("'2025-11-18T00:00:00'")
        ->and($query['endDateTime'])->toBe("'2025-11-18T00:59:59'")
        ->and($query)->not->toHaveKey('$filter')
        ->and($query)->not->toHaveKey('continuationToken');
});

test('query includes filter when provided', function () {
    $request = new GetActivityEvents(
        startDateTime: '2025-11-18T00:00:00',
        endDateTime: '2025-11-18T00:59:59',
        filter: "Activity eq 'ViewReport'",
    );
    $query = $request->query()->all();

    expect($query['$filter'])->toBe("Activity eq 'ViewReport'");
});

test('when continuationToken is set only it appears in the query', function () {
    $request = new GetActivityEvents(
        startDateTime: '2025-11-18T00:00:00',
        endDateTime: '2025-11-18T00:59:59',
        filter: "Activity eq 'ViewReport'",
        continuationToken: 'some-token',
    );
    $query = $request->query()->all();

    expect($query)->toHaveKey('continuationToken', 'some-token')
        ->and($query)->not->toHaveKey('startDateTime')
        ->and($query)->not->toHaveKey('endDateTime')
        ->and($query)->not->toHaveKey('$filter');
});

test('query is empty when no params provided', function () {
    $request = new GetActivityEvents;
    expect($request->query()->all())->toBe([]);
});

//
// Response DTO mapping
//

test('response maps to ActivityEventsResponse DTO', function () {
    $mockClient = new MockClient([
        GetActivityEvents::class => new PowerBIFixture('admin/activity/get-activity-events'),
    ]);

    $connector = new PowerBIServicePrincipal;
    $response = $connector->send(new GetActivityEvents, mockClient: $mockClient);

    expect($response->status())->toBe(200)
        ->and($response->dto())->toBeInstanceOf(ActivityEventsResponse::class);
});

test('response includes activity event entities as collection', function () {
    $mockClient = new MockClient([
        GetActivityEvents::class => new PowerBIFixture('admin/activity/get-activity-events'),
    ]);

    $connector = new PowerBIServicePrincipal;
    $dto = $connector->send(new GetActivityEvents, mockClient: $mockClient)->dto();

    expect($dto->activityEventEntities)->toBeInstanceOf(Collection::class)
        ->and($dto->activityEventEntities)->toHaveCount(3);

    foreach ($dto->activityEventEntities as $event) {
        expect($event)->toBeInstanceOf(ActivityEvent::class);
    }
});

test('activity event maps all required fields', function () {
    $mockClient = new MockClient([
        GetActivityEvents::class => new PowerBIFixture('admin/activity/get-activity-events'),
    ]);

    $connector = new PowerBIServicePrincipal;
    $dto = $connector->send(new GetActivityEvents, mockClient: $mockClient)->dto();

    $event = $dto->activityEventEntities->first();
    expect($event->id)->toBe('evt-001')
        ->and($event->creationTime)->toBeInstanceOf(Carbon::class)
        ->and($event->operation)->toBe('ViewReport')
        ->and($event->activity)->toBe('ViewReport')
        ->and($event->userId)->toBe('user@example.com')
        ->and($event->isSuccess)->toBeTrue();
});

test('activity event maps optional fields when present', function () {
    $mockClient = new MockClient([
        GetActivityEvents::class => new PowerBIFixture('admin/activity/get-activity-events'),
    ]);

    $connector = new PowerBIServicePrincipal;
    $dto = $connector->send(new GetActivityEvents, mockClient: $mockClient)->dto();

    $event = $dto->activityEventEntities->first();
    expect($event->workspaceId)->toBeString()
        ->and($event->workspaceName)->toBe('Sales Analytics')
        ->and($event->reportId)->toBeString()
        ->and($event->reportName)->toBe('Sales Report')
        ->and($event->reportType)->toBe('PowerBIReport')
        ->and($event->datasetId)->toBeString()
        ->and($event->datasetName)->toBe('Sales Dataset')
        ->and($event->clientIp)->toBe('192.168.1.1')
        ->and($event->userAgent)->toBeString();
});

test('activity event optional fields are null when absent', function () {
    $mockClient = new MockClient([
        GetActivityEvents::class => new PowerBIFixture('admin/activity/get-activity-events'),
    ]);

    $connector = new PowerBIServicePrincipal;
    $dto = $connector->send(new GetActivityEvents, mockClient: $mockClient)->dto();

    // Third event has only required fields
    $sparse = $dto->activityEventEntities->get(2);
    expect($sparse->isSuccess)->toBeFalse()
        ->and($sparse->workspaceId)->toBeNull()
        ->and($sparse->reportId)->toBeNull()
        ->and($sparse->datasetId)->toBeNull()
        ->and($sparse->clientIp)->toBeNull()
        ->and($sparse->userAgent)->toBeNull();
});

test('activity event falls back to Operation when Activity field is absent', function () {
    $mockClient = new MockClient([
        GetActivityEvents::class => new PowerBIFixture('admin/activity/get-activity-events'),
    ]);

    $connector = new PowerBIServicePrincipal;
    $dto = $connector->send(new GetActivityEvents, mockClient: $mockClient)->dto();

    // Third event has no Activity field — should fall back to Operation
    $event = $dto->activityEventEntities->get(2);
    expect($event->activity)->toBe('ViewDashboard')
        ->and($event->operation)->toBe('ViewDashboard');
});

test('response includes continuation token when present', function () {
    $mockClient = new MockClient([
        GetActivityEvents::class => new PowerBIFixture('admin/activity/get-activity-events'),
    ]);

    $connector = new PowerBIServicePrincipal;
    $dto = $connector->send(new GetActivityEvents, mockClient: $mockClient)->dto();

    expect($dto->continuationToken)->toBe('next-page-token-example')
        ->and($dto->continuationUri)->toContain('continuationToken=next-page-token-example');
});

test('response has null continuation token on last page', function () {
    $mockClient = new MockClient([
        GetActivityEvents::class => new PowerBIFixture('admin/activity/get-activity-events-page2'),
    ]);

    $connector = new PowerBIServicePrincipal;
    $dto = $connector->send(new GetActivityEvents, mockClient: $mockClient)->dto();

    expect($dto->continuationToken)->toBeNull()
        ->and($dto->continuationUri)->toBeNull();
});

//
// Pagination tests
//

test('getAllPages merges results across all pages', function () {
    $callCount = 0;
    $mockClient = new MockClient([
        GetActivityEvents::class => function () use (&$callCount) {
            $responses = [
                new PowerBIFixture('admin/activity/get-activity-events'),       // Page 1: 3 events + token
                new PowerBIFixture('admin/activity/get-activity-events-page2'), // Page 2: 2 events, no token
            ];

            return $responses[$callCount++] ?? $responses[1];
        },
    ]);

    $connector = new PowerBIServicePrincipal;
    $request = new GetActivityEvents(
        startDateTime: '2025-11-18T00:00:00',
        endDateTime: '2025-11-18T00:59:59',
    );
    $all = $request->getAllPages($connector, $mockClient);

    expect($all)->toBeInstanceOf(Collection::class)
        ->and($all)->toHaveCount(5)
        ->and($all->pluck('id')->toArray())->toEqual(['evt-001', 'evt-002', 'evt-003', 'evt-004', 'evt-005']);
});

test('getAllPages handles single page with no continuation token', function () {
    $mockClient = new MockClient([
        GetActivityEvents::class => new PowerBIFixture('admin/activity/get-activity-events-page2'),
    ]);

    $connector = new PowerBIServicePrincipal;
    $all = (new GetActivityEvents)->getAllPages($connector, $mockClient);

    expect($all)->toHaveCount(2)
        ->and($all->first()->id)->toBe('evt-004');
});

test('withOnlyContinuationToken creates request with only the token', function () {
    $original = new GetActivityEvents(
        startDateTime: '2025-11-18T00:00:00',
        endDateTime: '2025-11-18T00:59:59',
        filter: "Activity eq 'ViewReport'",
    );

    $method = new ReflectionMethod($original, 'withOnlyContinuationToken');
    $method->setAccessible(true);
    $next = $method->invoke($original, 'my-token');

    $query = $next->query()->all();
    expect($query)->toHaveKey('continuationToken', 'my-token')
        ->and($query)->not->toHaveKey('startDateTime')
        ->and($query)->not->toHaveKey('endDateTime')
        ->and($query)->not->toHaveKey('$filter');
});
