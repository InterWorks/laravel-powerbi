<?php

namespace InterWorks\PowerBI\DTO;

use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class Group implements WithResponse
{
    use HasResponse;

    public function __construct(
        public readonly string $id,
        public readonly bool $isReadOnly,
        public readonly bool $isOnDedicatedCapacity,
        public readonly string $type,
        public readonly string $name,
    ) {}

    /**
     * Create a ConnectedApplicationCollection from an array
     *
     * @param  array{
     *    id: string,
     *    isReadOnly: bool,
     *    isOnDedicatedCapacity: bool,
     *    type: string,
     *    name: string
     * } $item
     */
    public static function fromItem(array $item): self
    {
        return new self(
            id: $item['id'],
            isReadOnly: $item['isReadOnly'],
            isOnDedicatedCapacity: $item['isOnDedicatedCapacity'],
            type: $item['type'],
            name: $item['name'],
        );
    }
}
