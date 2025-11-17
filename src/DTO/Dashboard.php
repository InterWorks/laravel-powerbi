<?php

namespace InterWorks\PowerBI\DTO;

use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class Dashboard implements WithResponse
{
    use HasResponse;

    public function __construct(
        public readonly string $id,
        public readonly string $displayName,
        public readonly bool $isReadOnly,
        public readonly string $embedUrl,
    ) {}

    /**
     * Create a DashboardsCollection from an array
     *
     * @param  array{
     *    id: string,
     *    displayName: string,
     *    isReadOnly: bool,
     *    embedUrl: string,
     * } $item
     */
    public static function fromItem(array $item): self
    {
        return new self(
            id: $item['id'],
            displayName: $item['displayName'],
            isReadOnly: $item['isReadOnly'],
            embedUrl: $item['embedUrl'],
        );
    }
}
