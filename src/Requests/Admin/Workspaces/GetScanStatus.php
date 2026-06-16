<?php

namespace InterWorks\PowerBI\Requests\Admin\Workspaces;

use InterWorks\PowerBI\DTO\ScanRequest;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

/**
 * Gets the status of a workspace metadata scan started by PostWorkspaceInfo.
 *
 * @see https://learn.microsoft.com/en-us/rest/api/power-bi/admin/workspace-info-get-scan-status
 */
class GetScanStatus extends Request
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
        return "/admin/workspaces/scanStatus/{$this->scanId}";
    }

    public function createDtoFromResponse(Response $response): mixed
    {
        /** @var array{ id: string, status: string, createdDateTime?: string, error?: array<string, mixed> } $data */
        $data = $response->json();

        return ScanRequest::fromArray($data);
    }
}
