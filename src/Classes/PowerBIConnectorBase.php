<?php

namespace InterWorks\PowerBI\Classes;

use Illuminate\Support\Facades\Config;
use InterWorks\PowerBI\Enums\CloudEnvironment;
use InterWorks\PowerBI\Enums\ConnectionAccountType;
use InterWorks\PowerBI\Exceptions\AccountTypeRestrictedException;
use InterWorks\PowerBI\Exceptions\UnauthorizedAdminAccessException;
use InterWorks\PowerBI\Requests\Concerns\HasAccountTypeRestrictions;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Throwable;

/**
 * Base connector for all Power BI API connectors.
 *
 * This abstract class provides shared functionality for all Power BI
 * connector types, including:
 * - Base URL resolution
 * - Account type restriction enforcement
 * - Custom exception handling
 * - Common properties and configuration
 */
abstract class PowerBIConnectorBase extends Connector
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;

    /** @var string The tenant ID to authenticate to */
    protected string $tenant;

    /** @var ConnectionAccountType The connection account type */
    protected ConnectionAccountType $connectionAccountType;

    /** @var CloudEnvironment The Microsoft cloud environment */
    protected CloudEnvironment $cloudEnvironment = CloudEnvironment::Commercial;

    public function boot(PendingRequest $pendingRequest): void
    {
        // Get the datetime at the moment the request is being prepared and add it as a header for logging purposes
        $pendingRequest->headers()->add('X-Start-Time', (new \DateTime)->getTimestamp());
    }

    /**
     * The Base URL of the API.
     *
     * Resolves to the correct Power BI REST API host for the connector's cloud environment.
     */
    public function resolveBaseUrl(): string
    {
        return $this->cloudEnvironment->baseUrl();
    }

    /**
     * Get the connection account type.
     */
    public function getConnectionAccountType(): ConnectionAccountType
    {
        return $this->connectionAccountType;
    }

    /**
     * Get the cloud environment in use by this connector.
     */
    public function getCloudEnvironment(): CloudEnvironment
    {
        return $this->cloudEnvironment;
    }

    /**
     * Resolve the cloud environment from an explicit override, falling back to config.
     *
     * @param  string|null  $cloudEnvironment  Explicit cloud environment identifier, or null to use config
     *
     * @throws \InvalidArgumentException When the value is not a supported cloud environment
     */
    protected function resolveCloudEnvironment(?string $cloudEnvironment): CloudEnvironment
    {
        if ($cloudEnvironment === null) {
            /** @var string $cloudEnvironment */
            $cloudEnvironment = Config::get('powerbi.cloud_environment', CloudEnvironment::Commercial->value);
        }

        return CloudEnvironment::fromString($cloudEnvironment);
    }

    /**
     * Send a request and automatically enforce account type restrictions.
     *
     * This method overrides the parent send method to automatically detect
     * requests with the HasAccountTypeRestrictions trait and enforce
     * access control before sending.
     */
    public function send(Request $request, ?MockClient $mockClient = null, ?callable $handleRetry = null): Response
    {
        // Check if the request has account type restrictions
        $this->enforceAccountTypeRestrictions($request);

        // Proceed with the normal send process
        return parent::send($request, $mockClient, $handleRetry);
    }

    //
    // Account Type Restriction Enforcement
    //

    /**
     * Enforce account type restrictions if the request implements the trait.
     *
     * @throws AccountTypeRestrictedException When the account type is restricted
     */
    protected function enforceAccountTypeRestrictions(Request $request): void
    {
        // Check if the request uses the HasAccountTypeRestrictions trait
        $uses = class_uses_recursive($request);

        if (! in_array(HasAccountTypeRestrictions::class, $uses, true)) {
            return;
        }

        // The trait guarantees the restrictedAccountTypes() method exists
        // Use method_exists to satisfy PHPStan while maintaining runtime safety
        if (! method_exists($request, 'restrictedAccountTypes')) {
            return; // @codeCoverageIgnore
        }

        // Get the restricted account types from the request
        /** @var array<ConnectionAccountType> $restrictedTypes */
        $restrictedTypes = $request->restrictedAccountTypes();

        // Check if the current account type is restricted
        if (in_array($this->connectionAccountType, $restrictedTypes, true)) {
            throw AccountTypeRestrictedException::make(
                $this->connectionAccountType,
                $request->getMethod(),
                $request->resolveEndpoint()
            );
        }
    }

    //
    // Error handling
    //

    /**
     * Get a custom exception for the failed request.
     *
     * This method provides detailed error messages for common failure scenarios,
     * particularly for unauthorized access to admin endpoints.
     */
    public function getRequestException(Response $response, ?Throwable $senderException): ?Throwable
    {
        // Handle unauthorized access to admin endpoints
        if ($response->status() === 401) {
            $endpoint = $response->getPendingRequest()->getRequest()->resolveEndpoint();

            if (str_starts_with($endpoint, '/admin')) {
                return UnauthorizedAdminAccessException::make($response, $endpoint);
            }
        }

        // Return null to use default Saloon exception handling
        return null;
    }
}
