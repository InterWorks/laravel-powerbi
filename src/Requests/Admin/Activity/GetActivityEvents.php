<?php

namespace InterWorks\PowerBI\Requests\Admin\Activity;

use Illuminate\Support\Collection;
use InterWorks\PowerBI\DTO\ActivityEvent;
use InterWorks\PowerBI\DTO\ActivityEventsResponse;
use InterWorks\PowerBI\Requests\Concerns\HasContinuationTokenPagination;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

/**
 * Retrieve audit activity events for a tenant.
 *
 * The API accepts a time window of up to 1 hour per request. Use getAllPages()
 * to follow continuation tokens automatically within that window.
 * For a full day, call this 24 times (one per hour) and merge results.
 *
 * Requires the admin Service Principal connector and the tenant setting
 * "Allow service principals to use read-only admin APIs".
 *
 * @see https://learn.microsoft.com/en-us/rest/api/power-bi/admin/get-activity-events
 */
class GetActivityEvents extends Request
{
    use HasContinuationTokenPagination;

    protected Method $method = Method::GET;

    /**
     * @param  string|null  $startDateTime  Window start in ISO 8601 format e.g. '2024-01-01T00:00:00'
     * @param  string|null  $endDateTime    Window end in ISO 8601 format e.g. '2024-01-01T00:59:59' (max 1 hour after start)
     * @param  string|null  $filter         OData filter e.g. "Activity eq 'ViewReport'"
     * @param  string|null  $continuationToken  Token from a previous response page
     */
    public function __construct(
        protected readonly ?string $startDateTime = null,
        protected readonly ?string $endDateTime = null,
        protected readonly ?string $filter = null,
        protected readonly ?string $continuationToken = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/admin/activityevents';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        $query = [];

        if ($this->continuationToken !== null) {
            $query['continuationToken'] = $this->continuationToken;

            return $query;
        }

        if ($this->startDateTime !== null) {
            $query['startDateTime'] = "'{$this->startDateTime}'";
        }

        if ($this->endDateTime !== null) {
            $query['endDateTime'] = "'{$this->endDateTime}'";
        }

        if ($this->filter !== null) {
            $query['$filter'] = $this->filter;
        }

        return $query;
    }

    public function createDtoFromResponse(Response $response): ActivityEventsResponse
    {
        /** @var array{activityEventEntities: array<int, array<string, mixed>>, continuationToken?: string, continuationUri?: string} $data */
        $data = $response->json();

        return ActivityEventsResponse::fromArray($data);
    }

    protected function withOnlyContinuationToken(string $token): static
    {
        // @phpstan-ignore return.type
        return new self(continuationToken: $token);
    }

    /**
     * @param  ActivityEventsResponse  $dto
     * @return Collection<int, ActivityEvent>
     */
    protected function extractCollectionFromDto(mixed $dto): Collection
    {
        return $dto->activityEventEntities;
    }

    /**
     * @param  ActivityEventsResponse  $dto
     */
    protected function extractContinuationToken(mixed $dto): ?string
    {
        return $dto->continuationToken;
    }
}
