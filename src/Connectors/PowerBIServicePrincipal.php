<?php

namespace InterWorks\PowerBI\Connectors;

use Illuminate\Support\Facades\Config;
use InterWorks\PowerBI\Classes\PowerBIConnectorBase;
use InterWorks\PowerBI\Enums\ConnectionAccountType;
use InvalidArgumentException;
use Saloon\Helpers\OAuth2\OAuthConfig;
use Saloon\Http\OAuth2\GetClientCredentialsTokenRequest;
use Saloon\Http\Request;
use Saloon\Traits\OAuth2\ClientCredentialsGrant;

/**
 * Power BI connector for Service Principal authentication using Client Credentials Grant.
 */
class PowerBIServicePrincipal extends PowerBIConnectorBase
{
    use ClientCredentialsGrant;

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
     *
     * @throws InvalidArgumentException When invalid account type is provided
     */
    public function __construct(
        ?string $tenant = null,
        ?string $clientId = null,
        ?string $clientSecret = null,
        ConnectionAccountType $connectionAccountType = ConnectionAccountType::ServicePrinciple,
    ) {
        // Validate that only Service Principal account types are used with this connector
        if ($connectionAccountType === ConnectionAccountType::AzureUser) {
            throw new InvalidArgumentException(
                'PowerBIServicePrincipal connector cannot be used with AzureUser account type. '.
                'Use PowerBIAzureUser connector instead.'
            );
        }

        $this->tenant = $tenant ?? Config::string('powerbi.tenant');
        $this->clientId = $clientId ?? Config::string('powerbi.client_id');
        $this->clientSecret = $clientSecret ?? Config::string('powerbi.client_secret');
        $this->connectionAccountType = $connectionAccountType;
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
     * Returns the Azure AD v1.0 token endpoint.
     */
    private function getTokenEndpoint(): string
    {
        return "https://login.windows.net/{$this->tenant}/oauth2/token";
    }

    /**
     * Returns the Power BI API resource URL.
     */
    private function getResourceUrl(): string
    {
        return 'https://analysis.windows.net/powerbi/api';
    }
}
