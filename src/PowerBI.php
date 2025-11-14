<?php

namespace InterWorks\PowerBI;

use Saloon\Helpers\OAuth2\OAuthConfig;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Http\OAuth2\GetClientCredentialsTokenRequest;
use Saloon\Traits\OAuth2\ClientCredentialsGrant;
use Saloon\Traits\Plugins\AcceptsJson;

class PowerBI extends Connector
{
    use AcceptsJson;
    use ClientCredentialsGrant;

    /** @var string The tenant ID to authenticate to */
    protected string $tenant;

    /** @var string The client ID for the Power BI application */
    protected string $clientId;

    /** @var string The client secret for the Power BI application */
    protected string $clientSecret;

    public function __construct()
    {
        $this->tenant = config()->string('powerbi.tenant');
        $this->clientId = config()->string('powerbi.client_id');
        $this->clientSecret = config()->string('powerbi.client_secret');
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
}
