<?php

namespace InterWorks\PowerBI\DTO;

use Illuminate\Support\Collection;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class Groups implements WithResponse
{
    use HasResponse;

    /**
     * Constructor
     *
     * @param  Collection<int, Group>  $groups
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
     * }> $data
     */
    public static function fromArray(array $data): self
    {
        $groups = collect($data)->map(function ($item) {
            return Group::fromItem($item);
        });

        return new self($groups);
    }
}
