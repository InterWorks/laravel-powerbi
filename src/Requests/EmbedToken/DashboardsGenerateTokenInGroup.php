<?php

namespace InterWorks\PowerBI\Requests\EmbedToken;

use InterWorks\PowerBI\DTO\EmbedToken;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class DashboardsGenerateTokenInGroup extends Request implements HasBody
{
    use HasJsonBody;

    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::POST;

    /**
     * Create a new request instance.
     */
    public function __construct(
        protected readonly string $groupId,
        protected readonly string $dashboardId,
        protected readonly string $accessLevel = 'View',
    ) {}

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return "/groups/{$this->groupId}/dashboards/{$this->dashboardId}/GenerateToken";
    }

    /**
     * @return array{
     *     accessLevel: string
     * }
     */
    protected function defaultBody(): array
    {
        return [
            'accessLevel' => $this->accessLevel,
        ];
    }

    public function createDtoFromResponse(Response $response): mixed
    {
        $data = $response->json();

        // @phpstan-ignore argument.type
        $dashboard = EmbedToken::fromItem($data);

        return $dashboard;
    }
}
