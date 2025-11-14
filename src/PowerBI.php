<?php

namespace InterWorks\PowerBI;

use InterWorks\PowerBI\Exceptions\UnauthorizedAdminAccessException;
use InvalidArgumentException;
use Saloon\Helpers\OAuth2\OAuthConfig;
use Saloon\Http\Connector;
use Saloon\Http\OAuth2\GetClientCredentialsTokenRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\OAuth2\ClientCredentialsGrant;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Throwable;

class PowerBI extends Connector
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;
    use ClientCredentialsGrant;

    /** @var string The tenant ID to authenticate to */
    protected string $tenant;

    /** @var string The client ID for the Power BI application */
    protected string $clientId;

    /** @var string The client secret for the Power BI application */
    protected string $clientSecret;

    /**
     * Create a new PowerBI connector instance.
     *
     * @param  string|null  $tenant  Optional tenant ID override
     * @param  string|null  $clientId  Optional client ID override
     * @param  string|null  $clientSecret  Optional client secret override
     *
     * @throws InvalidArgumentException When partial credentials are provided
     */
    public function __construct(
        ?string $tenant = null,
        ?string $clientId = null,
        ?string $clientSecret = null
    ) {
        // Validate that if any parameter is provided, all must be provided
        $providedParams = array_filter([
            'tenant' => $tenant,
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
        ], fn ($value) => $value !== null);

        if (count($providedParams) > 0 && count($providedParams) < 3) {
            throw new InvalidArgumentException(
                'When overriding credentials, all three parameters (tenant, clientId, clientSecret) must be provided'
            );
        }

        $this->tenant = $tenant ?? config()->string('powerbi.tenant');
        $this->clientId = $clientId ?? config()->string('powerbi.client_id');
        $this->clientSecret = $clientSecret ?? config()->string('powerbi.client_secret');
    }

    /**
     * The Base URL of the API.
     */
    public function resolveBaseUrl(): string
    {
        return 'https://api.powerbi.com/v1.0/myorg';
    }

    /**
     * The OAuth2 configuration
     */
    protected function defaultOauthConfig(): OAuthConfig
    {
        return OAuthConfig::make()
            ->setClientId($this->clientId)
            ->setClientSecret($this->clientSecret)
            ->setTokenEndpoint($this->getAccessTokenEndpoint())
            ->setRequestModifier(function (Request $request) {
                /** @var GetClientCredentialsTokenRequest $request */
                // Add the resource to the body
                $request->body()->add('resource', $this->getResourceUrl());
            });
    }

    /**
     * Returns the access token endpoint.
     */
    private function getAccessTokenEndpoint(): string
    {
        return "https://login.windows.net/{$this->tenant}/oauth2/token";
    }

    /**
     * Returns the resource URL.
     */
    private function getResourceUrl(): string
    {
        return 'https://analysis.windows.net/powerbi/api';
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
