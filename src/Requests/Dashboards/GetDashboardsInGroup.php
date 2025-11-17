<?php

namespace InterWorks\PowerBI\Requests\Dashboards;

use InterWorks\PowerBI\DTO\Dashboards;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetDashboardsInGroup extends Request
{
    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    /**
     * Create a new request instance.
     *
     * @param string $groupId
     */
    public function __construct(protected readonly string $groupId) {}

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return "/groups/{$this->groupId}/dashboards";
    }

    public function createDtoFromResponse(Response $response): mixed
    {
        $data = $response->json();

        // @phpstan-ignore argument.type
        $dashboards = Dashboards::fromArray($data['value']);

        return $dashboards;
    }
}
