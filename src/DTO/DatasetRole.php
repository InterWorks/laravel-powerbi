<?php

namespace InterWorks\PowerBI\DTO;

use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

/**
 * A single Row-Level Security (RLS) role defined in a Power BI dataset.
 *
 * The `name` is what must be passed in the `roles` array when generating an
 * embed token. `tablePermissions` holds the DAX filter expressions per table.
 */
class DatasetRole implements WithResponse
{
    use HasResponse;

    /**
     * @param  array<int, array<string, mixed>>  $members
     * @param  array<int, array<string, mixed>>  $tablePermissions
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $modelPermission = null,
        public readonly array $members = [],
        public readonly array $tablePermissions = [],
    ) {}

    /**
     * @param  array{
     *    name: string,
     *    modelPermission?: string,
     *    members?: array<int, array<string, mixed>>,
     *    tablePermissions?: array<int, array<string, mixed>>
     * } $item
     */
    public static function fromItem(array $item): self
    {
        return new self(
            name: $item['name'],
            modelPermission: $item['modelPermission'] ?? null,
            members: $item['members'] ?? [],
            tablePermissions: $item['tablePermissions'] ?? [],
        );
    }
}
