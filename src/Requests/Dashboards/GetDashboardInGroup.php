<?php

namespace InterWorks\PowerBI\Requests\Dashboards;

use InterWorks\PowerBI\DTO\Dashboard;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetDashboardInGroup extends Request
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
        protected readonly string $dashboardId
    ) {}

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return "/groups/{$this->groupId}/dashboards/{$this->dashboardId}";
    }

    public function createDtoFromResponse(Response $response): mixed
    {
        $data = $response->json();

        // @phpstan-ignore argument.type
        $dashboard = Dashboard::fromItem($data);

        return $dashboard;
    }
}
