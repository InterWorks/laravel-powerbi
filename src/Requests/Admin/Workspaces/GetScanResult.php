<?php

namespace InterWorks\PowerBI\Requests\Admin\Workspaces;

use InterWorks\PowerBI\DTO\ScanResult;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

/**
 * Gets the result of a completed workspace metadata scan.
 *
 * Only call once GetScanStatus reports the scan as "Succeeded". The result
 * is retained by Power BI for a limited time after completion.
 *
 * @see https://learn.microsoft.com/en-us/rest/api/power-bi/admin/workspace-info-get-scan-result
 */
class GetScanResult extends Request
{
    protected Method $method = Method::GET;

    /**
     * @param  string  $scanId  The scan request ID returned by PostWorkspaceInfo.
     */
    public function __construct(
        protected readonly string $scanId,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/admin/workspaces/scanResult/{$this->scanId}";
    }

    public function createDtoFromResponse(Response $response): mixed
    {
        /** @var array{ workspaces?: array<int, array<string, mixed>> } $data */
        $data = $response->json();

        return ScanResult::fromArray($data);
    }
}
