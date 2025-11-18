<?php

namespace InterWorks\PowerBI\Exceptions;

use InterWorks\PowerBI\Enums\ConnectionAccountType;
use Saloon\Enums\Method;

/**
 * Exception thrown when an account type attempts to access a restricted endpoint.
 *
 * Power BI REST API enforces different access levels based on authentication type:
 *
 * - **Service Principal** (non-admin): Can access most endpoints but NOT individual resource
 *   endpoints like /reports/{id} or /dashboards/{id}. Must use group-scoped endpoints instead
 *   (e.g., /groups/{groupId}/reports/{reportId}).
 *
 * - **Service Principal (Admin)**: Can access admin endpoints (/admin/*) that require
 *   Power BI Service Administrator rights. Configure via separate admin credentials.
 *
 * - **Azure User**: Master user accounts can access all non-admin endpoints including
 *   individual resource endpoints.
 *
 * To resolve this issue:
 * - For Service Principal accounts: Use group-scoped endpoints instead of top-level resource endpoints
 * - For Admin access: Ensure the service principal has Power BI Service Administrator rights
 * - For individual resources: Use Azure User (master user) authentication or switch to group-scoped endpoints
 *
 * @see https://learn.microsoft.com/en-us/power-bi/developer/embedded/embed-service-principal
 * @see https://learn.microsoft.com/en-us/rest/api/power-bi/
 */
class AccountTypeRestrictedException extends \RuntimeException
{
    /**
     * Create exception for a restricted endpoint access attempt.
     */
    public static function make(
        ConnectionAccountType $accountType,
        Method $method,
        string $endpoint
    ): self {
        $message = sprintf(
            "Account type '%s' cannot access %s %s",
            $accountType->value,
            $method->value,
            $endpoint
        );

        return new self($message);
    }
}
