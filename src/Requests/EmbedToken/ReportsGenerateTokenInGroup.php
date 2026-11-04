<?php

namespace InterWorks\PowerBI\Requests\EmbedToken;

use InterWorks\PowerBI\DTO\EmbedToken;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class ReportsGenerateTokenInGroup extends Request implements HasBody
{
    use HasJsonBody;

    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::POST;

    /**
     * Create a new request instance.
     *
     * @param  array<int, array{username: string, roles: array<int, string>, datasets: array<int, string>}>  $identities
     */
    public function __construct(
        protected readonly string $groupId,
        protected readonly string $reportId,
        protected readonly string $accessLevel = 'View',
        protected readonly array $identities = [],
    ) {}

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return "/groups/{$this->groupId}/reports/{$this->reportId}/GenerateToken";
    }

    /**
     * @return array{accessLevel: string, identities?: array<int, array{username: string, roles: array<int, string>, datasets: array<int, string>}>}
     */
    protected function defaultBody(): array
    {
        $body = ['accessLevel' => $this->accessLevel];

        if ($this->identities !== []) {
            $body['identities'] = $this->identities;
        }

        return $body;
    }

    public function createDtoFromResponse(Response $response): mixed
    {
        $data = $response->json();

        // @phpstan-ignore argument.type
        $report = EmbedToken::fromItem($data);

        return $report;
    }
}
