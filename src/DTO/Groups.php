<?php

namespace InterWorks\PowerBI\DTO;

use Illuminate\Support\Collection;
use InterWorks\PowerBI\DTO\Group;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class Groups implements WithResponse
{
    use HasResponse;

    /**
     * Constructor
     *
     * @param Collection<int, Group> $groups
     */
    public function __construct(
        public readonly Collection $groups,
    ) {}

    /**
     * Create a ConnectedApplicationCollection from an array
     *
     * @param  array<int, array{
     *    id: string,
     *    isReadOnly: bool,
     *    isOnDedicatedCapacity: bool,
     *    type: string,
     *    name: string
     * }> $data The array to parse.
     *
     * @return self
    */
    public static function fromArray(array $data): self
    {
        $groups = collect($data)->map(function ($item) {
            return new Group(
                id: $item['id'],
                isReadOnly: $item['isReadOnly'],
                isOnDedicatedCapacity: $item['isOnDedicatedCapacity'],
                type: $item['type'],
                name: $item['name'],
            );
        });

        return new self($groups);
    }
}
