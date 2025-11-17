<?php

namespace InterWorks\PowerBI\Requests\Reports;

use InterWorks\PowerBI\DTO\Report;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetReportInGroup extends Request
{
    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    /**
     * Create a new request instance.
     */
    public function __construct(
        protected readonly string $groupId,
        protected readonly string $reportId
    ) {}

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return "/groups/{$this->groupId}/reports/{$this->reportId}";
    }

    public function createDtoFromResponse(Response $response): mixed
    {
        $data = $response->json();

        // @phpstan-ignore argument.type
        $report = Report::fromItem($data);

        return $report;
    }
}
