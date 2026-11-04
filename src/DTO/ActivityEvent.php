<?php

namespace InterWorks\PowerBI\DTO;

use Carbon\Carbon;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

/**
 * A single activity event from the Power BI Activity Events API.
 *
 * Not all fields are present in every event — availability depends on the
 * activity type (e.g. ReportId is only set for report-related operations).
 */
class ActivityEvent implements WithResponse
{
    use HasResponse;

    public function __construct(
        public readonly string $id,
        public readonly Carbon $creationTime,
        public readonly string $operation,
        public readonly string $activity,
        public readonly string $userId,
        public readonly bool $isSuccess,
        public readonly ?string $workspaceId = null,
        public readonly ?string $workspaceName = null,
        public readonly ?string $reportId = null,
        public readonly ?string $reportName = null,
        public readonly ?string $reportType = null,
        public readonly ?string $datasetId = null,
        public readonly ?string $datasetName = null,
        public readonly ?string $itemName = null,
        public readonly ?string $clientIp = null,
        public readonly ?string $userAgent = null,
        public readonly ?string $requestId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $item
     */
    public static function fromItem(array $item): self
    {
        return new self(
            id: $item['Id'],
            creationTime: Carbon::parse($item['CreationTime']),
            operation: $item['Operation'],
            activity: $item['Activity'] ?? $item['Operation'],
            userId: $item['UserId'],
            isSuccess: (bool) ($item['IsSuccess'] ?? true),
            workspaceId: $item['WorkspaceId'] ?? null,
            workspaceName: $item['WorkSpaceName'] ?? null,
            reportId: $item['ReportId'] ?? null,
            reportName: $item['ReportName'] ?? null,
            reportType: $item['ReportType'] ?? null,
            datasetId: $item['DatasetId'] ?? null,
            datasetName: $item['DatasetName'] ?? null,
            itemName: $item['ItemName'] ?? null,
            clientIp: $item['ClientIP'] ?? null,
            userAgent: $item['UserAgent'] ?? null,
            requestId: $item['RequestId'] ?? null,
        );
    }
}
