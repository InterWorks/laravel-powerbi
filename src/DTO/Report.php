<?php

namespace InterWorks\PowerBI\DTO;

use InterWorks\PowerBI\Enums\ReportType;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class Report implements WithResponse
{
    use HasResponse;

    /**
     * @param array<int, array<string, mixed>> $users
     * @param array<int, array<string, mixed>> $subscriptions
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $appId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $isOwnedByMe,
        public readonly ReportType $reportType,
        public readonly string $datasetId,
        public readonly string $datasetWorkspaceId,
        public readonly string $webUrl,
        public readonly string $embedUrl,
        public readonly array $users,
        public readonly array $subscriptions,
        public readonly int $reportFlags
    ) {}
}
