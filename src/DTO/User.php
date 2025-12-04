<?php

namespace InterWorks\PowerBI\DTO;

use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class User implements WithResponse
{
    use HasResponse;

    public function __construct(
        public readonly ?string $emailAddress = null,
        public readonly ?string $displayName = null,
        public readonly ?string $identifier = null,
        public readonly ?string $userPrincipalName = null,
    ) {}

    /**
     * @param  array{
     *    emailAddress?: string,
     *    displayName?: string,
     *    identifier?: string,
     *    userPrincipalName?: string
     * } $item
     */
    public static function fromItem(array $item): self
    {
        return new self(
            emailAddress: $item['emailAddress'] ?? null,
            displayName: $item['displayName'] ?? null,
            identifier: $item['identifier'] ?? null,
            userPrincipalName: $item['userPrincipalName'] ?? null,
        );
    }
}
