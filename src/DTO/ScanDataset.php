<?php

namespace InterWorks\PowerBI\DTO;

use Illuminate\Support\Collection;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

/**
 * A dataset (semantic model) as returned by the Scanner API, carrying its RLS roles.
 */
class ScanDataset implements WithResponse
{
    use HasResponse;

    /**
     * @param  Collection<int, DatasetRole>  $roles
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $name,
        public readonly ?string $configuredBy,
        public readonly Collection $roles,
    ) {}

    /**
     * @param  array{
     *    id: string,
     *    name?: string,
     *    configuredBy?: string,
     *    roles?: array<int, array<string, mixed>>
     * } $item
     */
    public static function fromItem(array $item): self
    {
        $roles = collect($item['roles'] ?? [])->map(
            // @phpstan-ignore argument.type
            fn (array $role): DatasetRole => DatasetRole::fromItem($role)
        );

        return new self(
            id: $item['id'],
            name: $item['name'] ?? null,
            configuredBy: $item['configuredBy'] ?? null,
            roles: $roles,
        );
    }
}
