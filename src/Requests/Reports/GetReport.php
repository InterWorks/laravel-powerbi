<?php

namespace InterWorks\PowerBI\Requests\Reports;

use InterWorks\PowerBI\DTO\Report;
use InterWorks\PowerBI\Enums\ConnectionAccountType;
use InterWorks\PowerBI\Requests\Concerns\HasAccountTypeRestrictions;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetReport extends Request
{
    use HasAccountTypeRestrictions;

    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    /**
     * Create a new request instance.
     */
    public function __construct(protected readonly string $reportId) {}

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return "/reports/{$this->reportId}";
    }

    /**
     * Service Principal accounts cannot access individual report endpoints.
     * Use GetReportInGroup instead.
     *
     * @return array<ConnectionAccountType>
     */
    public function restrictedAccountTypes(): array
    {
        return [
            ConnectionAccountType::ServicePrincipal,
            ConnectionAccountType::AdminServicePrincipal,
        ];
    }

    public function createDtoFromResponse(Response $response): mixed
    {
        $data = $response->json();

        // @phpstan-ignore argument.type
        $report = Report::fromItem($data);

        return $report;
    }
}
