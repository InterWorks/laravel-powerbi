<?php

namespace InterWorks\PowerBI\Enums;

enum ConnectionAccountType: string
{
    case AzureUser = 'AzureUser';
    case ServicePrincipal = 'ServicePrincipal';
    case AdminServicePrincipal = 'AdminServicePrincipal';

    public static function fromString(string $value): self
    {
        return match ($value) {
            'AzureUser' => self::AzureUser,
            'ServicePrincipal' => self::ServicePrincipal,
            'AdminServicePrincipal' => self::AdminServicePrincipal,
            default => throw new \InvalidArgumentException("Invalid ConnectionAccountType: $value"),
        };
    }
}
