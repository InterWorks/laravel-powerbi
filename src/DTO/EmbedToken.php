<?php

namespace InterWorks\PowerBI\DTO;

use Carbon\Carbon;
use Exception;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class EmbedToken implements WithResponse
{
    use HasResponse;

    public function __construct(
        public readonly string $token,
        public readonly string $tokenId,
        public readonly Carbon $expiration,
    ) {}

    /**
     * Create a ConnectedApplicationCollection from an array
     *
     * @param  array{
     *    token: string,
     *    tokenId: string,
     *    expiration: Carbon,
     * } $item
     */
    public static function fromItem(array $item): self
    {
        return new self(
            token: $item['token'],
            tokenId: $item['tokenId'],
            expiration: Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $item['expiration']) ?? throw new Exception('Invalid expiration date format'),
        );
    }
}
