<?php

namespace InterWorks\PowerBI\DTO;

use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class Group implements WithResponse
{
    use HasResponse;

    public function __construct(
        public readonly string $id,
        public readonly bool $isReadOnly,
        public readonly bool $isOnDedicatedCapacity,
        public readonly string $type,
        public readonly string $name,
    ) {}
}
