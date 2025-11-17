<?php

namespace InterWorks\PowerBI\DTO;

use InterWorks\PowerBI\Enums\ReportType;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class Report implements WithResponse
{
    use HasResponse;

    /**
     * @param  array<int, array<string, mixed>>  $users
     * @param  array<int, array<string, mixed>>  $subscriptions
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

    /**
     * Create a Reports collection from an array
     *
     * @param  array{
     *    id: string,
     *    appId: ?string,
     *    name: string,
     *    description: ?string,
     *    isOwnedByMe: bool,
     *    reportType: string,
     *    datasetId: string,
     *    datasetWorkspaceId: string,
     *    webUrl: string,
     *    embedUrl: string,
     *    users: array<int, array<string, mixed>>,
     *    subscriptions: array<int, array<string, mixed>>,
     *    reportFlags: int,
     * } $item
     */
    public static function fromItem(array $item): self
    {
        return new self(
            id: $item['id'],
            appId: $item['appId'] ?? null,
            name: $item['name'],
            description: $item['description'] ?? null,
            isOwnedByMe: $item['isOwnedByMe'],
            reportType: ReportType::fromString($item['reportType']),
            datasetId: $item['datasetId'],
            datasetWorkspaceId: $item['datasetWorkspaceId'],
            webUrl: $item['webUrl'],
            embedUrl: $item['embedUrl'],
            users: $item['users'],
            subscriptions: $item['subscriptions'],
            reportFlags: $item['reportFlags']
        );
    }
}
