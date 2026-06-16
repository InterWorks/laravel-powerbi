<?php

namespace InterWorks\PowerBI\DTO;

use Illuminate\Support\Collection;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class ActivityEventsResponse implements WithResponse
{
    use HasResponse;

    /**
     * @param  Collection<int, ActivityEvent>  $activityEventEntities
     */
    public function __construct(
        public readonly Collection $activityEventEntities,
        public readonly ?string $continuationToken = null,
        public readonly ?string $continuationUri = null,
    ) {}

    /**
     * @param  array{
     *    activityEventEntities: array<int, array<string, mixed>>,
     *    continuationToken?: string,
     *    continuationUri?: string
     * } $data
     */
    public static function fromArray(array $data): self
    {
        $events = collect($data['activityEventEntities'] ?? [])
            ->map(fn (array $item): ActivityEvent => ActivityEvent::fromItem($item));

        return new self(
            activityEventEntities: $events,
            continuationToken: $data['continuationToken'] ?? null,
            continuationUri: $data['continuationUri'] ?? null,
        );
    }
}
