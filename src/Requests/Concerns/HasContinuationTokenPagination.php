<?php

namespace InterWorks\PowerBI\Requests\Concerns;

use Illuminate\Support\Collection;
use Saloon\Http\Connector;

/**
 * Trait to handle automatic pagination through continuation token-based API responses.
 *
 * When an endpoint supports continuation tokens, the first request includes all
 * filter parameters (e.g., artifactTypes), but subsequent requests must ONLY
 * include the continuation token and required path parameters.
 *
 * Usage:
 * ```php
 * $request = new GetUserArtifactAccessAsAdmin(
 *     userId: 'user@example.com',
 *     artifactTypes: [ArtifactType::Report, ArtifactType::Dashboard]
 * );
 * $allResults = $request->getAllPages($connector);
 * // Returns Collection with all items across all pages
 * ```
 *
 * Request classes must implement three methods:
 * - withOnlyContinuationToken(string): Create minimal request for continuation
 * - extractCollectionFromDto(mixed): Extract collection from DTO
 * - extractContinuationToken(mixed): Extract token from DTO (null if last page)
 */
trait HasContinuationTokenPagination
{
    /**
     * Fetch all pages of results automatically by following continuation tokens.
     *
     * The first request uses all parameters from the original request.
     * Subsequent requests use only required parameters + continuation token.
     *
     * @param  Connector  $connector  The connector to send requests through
     * @param  \Saloon\Http\Faking\MockClient|null  $mockClient  Optional mock client for testing
     * @return Collection<int, mixed> All results merged across all pages
     */
    public function getAllPages(Connector $connector, ?\Saloon\Http\Faking\MockClient $mockClient = null): Collection
    {
        $allResults = collect();
        $continuationToken = null;

        do {
            // First call: use original request with all parameters
            // Subsequent calls: minimal request with only required params + token
            $request = ($continuationToken === null)
                ? $this
                : $this->withOnlyContinuationToken("'$continuationToken'"); // Token MUST be in single quotes

            $response = $connector->send($request, $mockClient);
            $dto = $response->dto();

            // Collect results from this page
            $allResults = $allResults->merge(
                // @phpstan-ignore argument.type
                $this->extractCollectionFromDto($dto)
            );

            // Get continuation token for next page (null if last page)
            // @phpstan-ignore argument.type
            $continuationToken = $this->extractContinuationToken($dto);
        } while ($continuationToken !== null);

        return $allResults;
    }

    /**
     * Create a new request instance with ONLY the continuation token
     * and required parameters (no filter parameters).
     *
     * Example:
     * ```php
     * protected function withOnlyContinuationToken(string $token): static
     * {
     *     return new self(
     *         userId: $this->userId,              // Required path param
     *         artifactTypes: [],                  // Filter param - must be empty
     *         continuationToken: $token           // Continuation token
     *     );
     * }
     * ```
     *
     * @param  string  $token  The continuation token from the previous response
     * @return static New request instance for fetching the next page
     */
    abstract protected function withOnlyContinuationToken(string $token): static;

    /**
     * Extract the collection of items from the DTO response.
     *
     * Example:
     * ```php
     * protected function extractCollectionFromDto($dto): Collection
     * {
     *     return $dto->artifactAccessEntities;
     * }
     * ```
     *
     * @param  mixed  $dto  The DTO returned from createDtoFromResponse()
     * @return Collection<int, mixed> The collection of items for this page
     */
    abstract protected function extractCollectionFromDto(mixed $dto): Collection;

    /**
     * Extract the continuation token from the DTO response.
     *
     * Example:
     * ```php
     * protected function extractContinuationToken($dto): ?string
     * {
     *     return $dto->continuationToken;
     * }
     * ```
     *
     * @param  mixed  $dto  The DTO returned from createDtoFromResponse()
     * @return string|null The continuation token, or null if this is the last page
     */
    abstract protected function extractContinuationToken(mixed $dto): ?string;
}
