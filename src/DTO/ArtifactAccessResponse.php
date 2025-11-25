<?php

namespace InterWorks\PowerBI\DTO;

use Illuminate\Support\Collection;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class ArtifactAccessResponse implements WithResponse
{
    use HasResponse;

    /**
     * @param  Collection<int, ArtifactAccessEntry>  $artifactAccessEntities
     */
    public function __construct(
        public readonly Collection $artifactAccessEntities,
        public readonly ?string $continuationToken = null,
        public readonly ?string $continuationUri = null,
    ) {}

    /**
     * @param  array{
     *    ArtifactAccessEntities: array<int, array{
     *       artifactId: string,
     *       displayName: string,
     *       artifactType: string,
     *       accessRight: string,
     *       shareType?: string,
     *       sharer?: array{
     *          emailAddress?: string,
     *          displayName?: string,
     *          identifier?: string,
     *          userPrincipalName?: string
     *       }
     *    }>,
     *    continuationToken?: string,
     *    continuationUri?: string
     * } $data
     */
    public static function fromArray(array $data): self
    {
        $entries = collect($data['ArtifactAccessEntities'])->map(function ($item) {
            return ArtifactAccessEntry::fromItem($item);
        });

        return new self(
            artifactAccessEntities: $entries,
            continuationToken: $data['continuationToken'] ?? null,
            continuationUri: $data['continuationUri'] ?? null,
        );
    }
}
