<?php

namespace InterWorks\PowerBI\DTO;

use Illuminate\Support\Collection;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

/**
 * A workspace entry within a Scanner API scan result, carrying its datasets.
 */
class ScanWorkspace implements WithResponse
{
    use HasResponse;

    /**
     * @param  Collection<int, ScanDataset>  $datasets
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $name,
        public readonly ?string $type,
        public readonly ?string $state,
        public readonly Collection $datasets,
    ) {}

    /**
     * @param  array{
     *    id: string,
     *    name?: string,
     *    type?: string,
     *    state?: string,
     *    datasets?: array<int, array<string, mixed>>
     * } $item
     */
    public static function fromItem(array $item): self
    {
        $datasets = collect($item['datasets'] ?? [])->map(
            // @phpstan-ignore argument.type
            fn (array $dataset): ScanDataset => ScanDataset::fromItem($dataset)
        );

        return new self(
            id: $item['id'],
            name: $item['name'] ?? null,
            type: $item['type'] ?? null,
            state: $item['state'] ?? null,
            datasets: $datasets,
        );
    }
}
