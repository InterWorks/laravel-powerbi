<?php

namespace InterWorks\PowerBI\Requests\Dashboards;

use InterWorks\PowerBI\DTO\Dashboard;
use InterWorks\PowerBI\Enums\ConnectionAccountType;
use InterWorks\PowerBI\Requests\Concerns\HasAccountTypeRestrictions;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetDashboard extends Request
{
    use HasAccountTypeRestrictions;

    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    /**
     * Create a new request instance.
     */
    public function __construct(protected readonly string $dashboardId) {}

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return "/dashboards/{$this->dashboardId}";
    }

    /**
     * Service Principal accounts cannot access individual dashboard endpoints.
     * Use GetDashboardInGroup instead.
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
        $dashboard = Dashboard::fromItem($data);

        return $dashboard;
    }
}
