<?php

namespace InterWorks\PowerBI\Enums;

enum ConnectionAccountType: string
{
    case AzureUser = 'AzureUser';
    case ServicePrinciple = 'ServicePrinciple';
    case AdminServicePrinciple = 'AdminServicePrinciple';

    public static function fromString(string $value): self
    {
        return match ($value) {
            'AzureUser' => self::AzureUser,
            'ServicePrinciple' => self::ServicePrinciple,
            'AdminServicePrinciple' => self::AdminServicePrinciple,
            default => throw new \InvalidArgumentException("Invalid ConnectionAccountType: $value"),
        };
    }
}
