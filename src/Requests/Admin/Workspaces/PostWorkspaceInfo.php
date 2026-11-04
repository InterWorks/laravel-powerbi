<?php

namespace InterWorks\PowerBI\Requests\Admin\Workspaces;

use InterWorks\PowerBI\DTO\ScanRequest;
use InvalidArgumentException;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Initiates a metadata scan for a set of workspaces (Scanner API).
 *
 * Returns a ScanRequest carrying the scan ID. Poll GetScanStatus until it is
 * complete, then fetch the metadata with GetScanResult.
 *
 * Requires the admin Service Principal connector and the tenant setting
 * "Allow service principals to use read-only admin APIs". Setting
 * $datasetSchema or $datasetExpressions to true additionally requires
 * "Enhanced metadata scanning" to be enabled in the tenant.
 *
 * @see https://learn.microsoft.com/en-us/rest/api/power-bi/admin/workspace-info-post-workspace-info
 */
class PostWorkspaceInfo extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param array<int, string> $workspaceIds The workspace IDs to scan (1 to 100).
     * @param bool $datasetSchema Return dataset schema (tables, columns, measures). Requires enhanced metadata scanning.
     * @param bool $datasetExpressions Return dataset expressions (DAX and Mashup queries). Requires enhanced metadata scanning.
     * @param bool $lineage Return lineage info (upstream dataflows, tiles, data source IDs).
     * @param bool $datasourceDetails Return data source details.
     * @param bool $getArtifactUsers Return user details for each item.
     */
    public function __construct(
        protected readonly array $workspaceIds,
        protected readonly bool  $datasetSchema = true,
        protected readonly bool  $datasetExpressions = false,
        protected readonly bool  $lineage = false,
        protected readonly bool  $datasourceDetails = false,
        protected readonly bool  $getArtifactUsers = false,
    )
    {
        $count = count($this->workspaceIds);

        if ($count < 1 || $count > 100) {
            // cf. https://learn.microsoft.com/en-us/rest/api/power-bi/admin/workspace-info-post-workspace-info
            throw new InvalidArgumentException('The number of workspace IDs must be between 1 and 100.');
        }
    }

    public function resolveEndpoint(): string
    {
        return '/admin/workspaces/getInfo';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        return [
            'datasetSchema' => $this->datasetSchema ? 'true' : 'false',
            'datasetExpressions' => $this->datasetExpressions ? 'true' : 'false',
            'lineage' => $this->lineage ? 'true' : 'false',
            'datasourceDetails' => $this->datasourceDetails ? 'true' : 'false',
            'getArtifactUsers' => $this->getArtifactUsers ? 'true' : 'false',
        ];
    }

    /**
     * @return array{ workspaces: array<int, string> }
     */
    protected function defaultBody(): array
    {
        return [
            'workspaces' => array_values($this->workspaceIds),
        ];
    }

    public function createDtoFromResponse(Response $response): mixed
    {
        /** @var array{ id: string, status: string, createdDateTime?: string, error?: array<string, mixed> } $data */
        $data = $response->json();

        return ScanRequest::fromArray($data);
    }
}
