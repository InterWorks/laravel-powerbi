<?php

namespace InterWorks\PowerBI\Exceptions;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Response;

/**
 * Exception thrown when a non-admin account attempts to access an admin endpoint.
 *
 * Power BI Admin APIs require specific admin permissions. When a regular user account
 * attempts to access these endpoints, a 401 Unauthorized response is returned.
 *
 * To resolve this issue:
 * - Ensure the service principal has Power BI Service Administrator rights in the Power BI Admin Portal
 * - Grant admin consent for the application in Azure Active Directory
 * - Use separate admin credentials configured via POWER_BI_ADMIN_CLIENT_ID
 *   and POWER_BI_ADMIN_CLIENT_SECRET environment variables
 *
 * @see https://learn.microsoft.com/en-us/power-bi/admin/service-admin-role
 */
class UnauthorizedAdminAccessException extends FatalRequestException
{
    /**
     * Create exception from a failed response to an admin endpoint.
     */
    public static function make(Response $response, string $endpoint): self
    {
        $statusCode = $response->status();

        $message = sprintf(
            "Unauthorized access to Power BI Admin endpoint '%s' (HTTP %d)",
            $endpoint,
            $statusCode
        );

        // Create a base exception to wrap
        $baseException = new \Exception($message, $statusCode);

        // FatalRequestException requires PendingRequest, not Response
        return new self($baseException, $response->getPendingRequest());
    }
}
