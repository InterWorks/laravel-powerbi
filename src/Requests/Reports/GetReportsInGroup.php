<?php

namespace InterWorks\PowerBI\Requests\Reports;

use InterWorks\PowerBI\DTO\Reports;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetReportsInGroup extends Request
{
    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    /**
     * Create a new request instance.
     */
    public function __construct(protected readonly string $groupId) {}

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return "/groups/{$this->groupId}/reports";
    }

    public function createDtoFromResponse(Response $response): mixed
    {
        $data = $response->json();

        // @phpstan-ignore argument.type
        $reports = Reports::fromArray($data['value']);

        return $reports;
    }
}
