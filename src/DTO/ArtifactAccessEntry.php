<?php

namespace InterWorks\PowerBI\DTO;

use InterWorks\PowerBI\Enums\ArtifactType;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class ArtifactAccessEntry implements WithResponse
{
    use HasResponse;

    public function __construct(
        public readonly string $artifactId,
        public readonly string $displayName,
        public readonly ArtifactType $artifactType,
        public readonly string $accessRight,
        public readonly ?string $shareType = null,
        public readonly ?User $sharer = null,
    ) {}

    /**
     * @param  array{
     *    artifactId: string,
     *    displayName: string,
     *    artifactType: string,
     *    accessRight: string,
     *    shareType?: string,
     *    sharer?: array{
     *       emailAddress?: string,
     *       displayName?: string,
     *       identifier?: string,
     *       userPrincipalName?: string
     *    }
     * } $item
     */
    public static function fromItem(array $item): self
    {
        return new self(
            artifactId: $item['artifactId'],
            displayName: $item['displayName'],
            artifactType: ArtifactType::fromString($item['artifactType']),
            accessRight: $item['accessRight'],
            shareType: $item['shareType'] ?? null,
            sharer: isset($item['sharer']) ? User::fromItem($item['sharer']) : null,
        );
    }
}
