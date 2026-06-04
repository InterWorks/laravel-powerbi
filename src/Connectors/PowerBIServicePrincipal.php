<?php

namespace InterWorks\PowerBI\Connectors;

use Illuminate\Support\Facades\Config;
use InterWorks\PowerBI\Classes\CloudEnvironment;
use InterWorks\PowerBI\Classes\PowerBIConnectorBase;
use InterWorks\PowerBI\Connectors\Traits\ConnectorCacheSettings;
use InterWorks\PowerBI\Enums\ConnectionAccountType;
use InvalidArgumentException;
use Saloon\CachePlugin\Contracts\Cacheable;
use Saloon\CachePlugin\Traits\HasCaching;
use Saloon\Helpers\OAuth2\OAuthConfig;
use Saloon\Http\OAuth2\GetClientCredentialsTokenRequest;
use Saloon\Http\Request;
use Saloon\Traits\OAuth2\ClientCredentialsGrant;

/**
 * Power BI connector for Service Principal authentication using Client Credentials Grant.
 */
class PowerBIServicePrincipal extends PowerBIConnectorBase implements Cacheable
{
    use ClientCredentialsGrant;
    use ConnectorCacheSettings;
    use HasCaching;

    /** @var string The client ID for the Power BI application */
    protected string $clientId;

    /** @var string The client secret for the Power BI application */
    protected string $clientSecret;

    /**
     * Create a new PowerBI Service Principal connector instance.
     *
     * @param  string  $tenant  The Azure AD tenant ID
     * @param  string  $clientId  The application (client) ID
     * @param  string  $clientSecret  The application client secret
     * @param  ConnectionAccountType  $connectionAccountType  The service principal account type
     * @param  string|null  $cloudEnvironment  Microsoft cloud environment (defaults to config, then commercial)
     *
     * @throws InvalidArgumentException When invalid account type is provided
     */
    public function __construct(
        ?string $tenant = null,
        ?string $clientId = null,
        ?string $clientSecret = null,
        ConnectionAccountType $connectionAccountType = ConnectionAccountType::ServicePrincipal,
        ?string $cloudEnvironment = null,
    ) {
        // Validate that only Service Principal account types are used with this connector
        if ($connectionAccountType === ConnectionAccountType::AzureUser) {
            throw new InvalidArgumentException(
                'PowerBIServicePrincipal connector cannot be used with AzureUser account type. '.
                'Use PowerBIAzureUser connector instead.'
            );
        }

        /** @var string $configTenant */
        $configTenant = Config::get('powerbi.tenant', '');
        /** @var string $configClientId */
        $configClientId = Config::get('powerbi.client_id', '');
        /** @var string $configClientSecret */
        $configClientSecret = Config::get('powerbi.client_secret', '');
        /** @var string $configCloudEnvironment */
        $configCloudEnvironment = Config::get('powerbi.cloud_environment', CloudEnvironment::COMMERCIAL);

        $this->tenant = $tenant ?? $configTenant;
        $this->clientId = $clientId ?? $configClientId;
        $this->clientSecret = $clientSecret ?? $configClientSecret;
        $this->connectionAccountType = $connectionAccountType;
        $this->cloudEnvironment = CloudEnvironment::normalize($cloudEnvironment ?? $configCloudEnvironment);

        // Configure caching based on package configuration
        $this->configureCaching();
    }

    /**
     * Configure caching based on package configuration.
     *
     * Disables caching if config('powerbi.cache.enabled') is false.
     * This must be called in the constructor before any requests are sent.
     */
    protected function configureCaching(): void
    {
        if (! (bool) Config::get('powerbi.cache.enabled', true)) {
            $this->disableCaching();
        }
    }

    /**
     * The OAuth2 configuration for Client Credentials Grant.
     *
     * Configures Azure AD v1.0 endpoints with the Power BI resource parameter.
     * The resource parameter is required for v1.0 token requests and specifies
     * the Power BI API as the target resource.
     */
    protected function defaultOauthConfig(): OAuthConfig
    {
        return OAuthConfig::make()
            ->setClientId($this->clientId)
            ->setClientSecret($this->clientSecret)
            ->setTokenEndpoint($this->getTokenEndpoint())
            ->setRequestModifier(function (Request $request) {
                /** @var GetClientCredentialsTokenRequest $request */
                // Add the Power BI resource to the request body (required for Azure AD v1.0)
                $request->body()->add('resource', $this->getResourceUrl());
            });
    }

    /**
     * Returns the Azure AD v1.0 token endpoint for the connector's cloud environment.
     */
    private function getTokenEndpoint(): string
    {
        return CloudEnvironment::getTokenEndpoint($this->cloudEnvironment, $this->tenant);
    }

    /**
     * Returns the Power BI API resource URL for the connector's cloud environment.
     */
    private function getResourceUrl(): string
    {
        return CloudEnvironment::getResourceUrl($this->cloudEnvironment);
    }
}
