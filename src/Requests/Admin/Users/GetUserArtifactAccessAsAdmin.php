<?php

namespace InterWorks\PowerBI\Requests\Admin\Users;

use Illuminate\Support\Collection;
use InterWorks\PowerBI\DTO\ArtifactAccessEntry;
use InterWorks\PowerBI\DTO\ArtifactAccessResponse;
use InterWorks\PowerBI\Enums\ArtifactType;
use InterWorks\PowerBI\Requests\Concerns\HasContinuationTokenPagination;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetUserArtifactAccessAsAdmin extends Request
{
    use HasContinuationTokenPagination;

    protected Method $method = Method::GET;

    /**
     * @param  string  $userId  Graph ID or user principal name (UPN) of the target user.
     * @param  array<ArtifactType>  $artifactTypes  An array of artifact types to filter results.
     * @param  string|null  $continuationToken  Token for pagination through result sets.
     */
    public function __construct(
        protected readonly string $userId,
        protected readonly ?array $artifactTypes = null,
        protected readonly ?string $continuationToken = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/admin/users/{$this->userId}/artifactAccess";
    }

    protected function defaultQuery(): array
    {
        $parameters = [];

        if ($this->getArtifactTypes() !== null) {
            $parameters['artifactTypes'] = $this->getArtifactTypes();
        }

        if ($this->continuationToken !== null) {
            $parameters['continuationToken'] = $this->continuationToken;
        }

        return $parameters;
    }

    public function createDtoFromResponse(Response $response): mixed
    {
        $data = $response->json();
        // @phpstan-ignore argument.type
        $artifactAccessResponse = ArtifactAccessResponse::fromArray($data);

        return $artifactAccessResponse;
    }

    public function getArtifactTypes(): ?string
    {
        if (is_null($this->artifactTypes) || empty($this->artifactTypes)) {
            return null;
        }

        $typeStrings = array_map(fn (ArtifactType $type) => $type->value, $this->artifactTypes);

        return implode(',', $typeStrings);
    }

    /**
     * Create a new request with only the continuation token.
     * Critical: artifactTypes must be null/empty on continuation requests.
     */
    protected function withOnlyContinuationToken(string $token): static
    {
        // @phpstan-ignore return.type
        return new self(
            userId: $this->userId,
            artifactTypes: null,
            continuationToken: $token
        );
    }

    /**
     * Extract the collection of artifact access entries from the DTO.
     *
     * @param  ArtifactAccessResponse  $dto
     * @return Collection<int, ArtifactAccessEntry>
     */
    protected function extractCollectionFromDto(mixed $dto): Collection
    {
        return $dto->artifactAccessEntities;
    }

    /**
     * Extract the continuation token from the DTO.
     *
     * @param  ArtifactAccessResponse  $dto
     */
    protected function extractContinuationToken(mixed $dto): ?string
    {
        return $dto->continuationToken;
    }
}
