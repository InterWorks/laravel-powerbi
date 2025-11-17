<?php

namespace InterWorks\PowerBI\DTO;

use Illuminate\Support\Collection;
use InterWorks\PowerBI\Enums\ReportType;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class Reports implements WithResponse
{
    use HasResponse;

    /**
     * Constructor
     *
     * @param  Collection<int, Report>  $reports
     */
    public function __construct(
        public readonly Collection $reports,
    ) {}

    /**
     * Create a Reports collection from an array
     *
     * @param  array<int, array{
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
     * }> $data The array to parse.
     */
    public static function fromArray(array $data): self
    {
        $reports = collect($data)->map(function ($item) {
            return new Report(
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
        });

        return new self($reports);
    }
}
