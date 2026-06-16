<?php

namespace InterWorks\PowerBI\DTO;

use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

/**
 * The scan request returned by the Scanner API's getInfo and scanStatus endpoints.
 *
 * Status progresses: NotStarted -> Running -> Succeeded | Failed.
 */
class ScanRequest implements WithResponse
{
    use HasResponse;

    /**
     * @param  array<string, mixed>|null  $error
     */
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly ?string $createdDateTime = null,
        public readonly ?array $error = null,
    ) {}

    /**
     * Whether the scan finished successfully and the result is ready to fetch.
     */
    public function isSucceeded(): bool
    {
        return strtolower($this->status) === 'succeeded';
    }

    /**
     * Whether the scan has reached a terminal state (succeeded or failed).
     */
    public function isComplete(): bool
    {
        return in_array(strtolower($this->status), ['succeeded', 'failed'], true);
    }

    /**
     * @param  array{
     *    id: string,
     *    status: string,
     *    createdDateTime?: string,
     *    error?: array<string, mixed>
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            status: $data['status'],
            createdDateTime: $data['createdDateTime'] ?? null,
            error: $data['error'] ?? null,
        );
    }
}
