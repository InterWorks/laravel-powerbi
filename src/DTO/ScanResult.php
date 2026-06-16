<?php

namespace InterWorks\PowerBI\DTO;

use Illuminate\Support\Collection;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

/**
 * The full metadata returned by the Scanner API's scanResult endpoint.
 */
class ScanResult implements WithResponse
{
    use HasResponse;

    /**
     * @param  Collection<int, ScanWorkspace>  $workspaces
     */
    public function __construct(
        public readonly Collection $workspaces,
    ) {}

    /**
     * @param  array{ workspaces?: array<int, array<string, mixed>> }  $data
     */
    public static function fromArray(array $data): self
    {
        $workspaces = collect($data['workspaces'] ?? [])->map(
            // @phpstan-ignore argument.type
            fn (array $workspace): ScanWorkspace => ScanWorkspace::fromItem($workspace)
        );

        return new self($workspaces);
    }
}
