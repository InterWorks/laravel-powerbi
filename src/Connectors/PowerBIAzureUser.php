<?php

namespace InterWorks\PowerBI\Connectors;

use Illuminate\Support\Facades\Config;
use InterWorks\PowerBI\Classes\PowerBIConnectorBase;
use InterWorks\PowerBI\Connectors\Traits\ConnectorCacheSettings;
use InterWorks\PowerBI\Enums\ConnectionAccountType;
use Saloon\CachePlugin\Contracts\Cacheable;
use Saloon\CachePlugin\Traits\HasCaching;
use Saloon\Helpers\OAuth2\OAuthConfig;
use Saloon\Traits\OAuth2\AuthorizationCodeGrant;

/**
 * Power BI connector for Azure User authentication using Authorization Code Grant.
 */
class PowerBIAzureUser extends PowerBIConnectorBase implements Cacheable
{
    use AuthorizationCodeGrant;
    use ConnectorCacheSettings;
    use HasCaching;

    /** @var string The client ID for the Power BI application */
    protected string $clientId;

    /** @var string The client secret for the Power BI application */
    protected string $clientSecret;

    /** @var string The OAuth callback/redirect URI */
    protected string $redirectUri;

    /** @var ConnectionAccountType The connection account type */
    protected ConnectionAccountType $connectionAccountType = ConnectionAccountType::AzureUser;

    /**
     * Create a new PowerBI Azure User connector instance.
     *
     * @param  string  $tenant  The Azure AD tenant ID
     * @param  string  $clientId  The application (client) ID
     * @param  string  $clientSecret  The application client secret
     * @param  string  $redirectUri  The OAuth callback/redirect URI
     * @param  string|null  $cloudEnvironment  Microsoft cloud environment (defaults to config, then commercial)
     *
     * @throws \InvalidArgumentException When an invalid cloud environment is provided
     */
    public function __construct(
        string $tenant,
        string $clientId,
        string $clientSecret,
        string $redirectUri,
        ?string $cloudEnvironment = null,
    ) {
        $this->tenant = $tenant;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->cloudEnvironment = $this->resolveCloudEnvironment($cloudEnvironment);

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
     * The OAuth2 configuration for Authorization Code Grant.
     */
    protected function defaultOauthConfig(): OAuthConfig
    {
        return OAuthConfig::make()
            ->setClientId($this->clientId)
            ->setClientSecret($this->clientSecret)
            ->setRedirectUri($this->redirectUri)
            ->setAuthorizeEndpoint($this->getAuthorizationEndpoint())
            ->setTokenEndpoint($this->getTokenEndpoint())
            // The authorize/token endpoints are absolute Microsoft Entra URLs
            // (login.microsoftonline.com / .us) that intentionally differ from the
            // Power BI API base URL. They are package-defined, not user input, so
            // Saloon v4's SSRF guard against absolute endpoint URLs is safe to relax here.
            ->setAllowBaseUrlOverride();
    }

    /**
     * Returns the authorization endpoint for the connector's cloud environment.
     */
    private function getAuthorizationEndpoint(): string
    {
        return $this->cloudEnvironment->authorizeEndpoint($this->tenant);
    }

    /**
     * Returns the token endpoint for the connector's cloud environment.
     */
    private function getTokenEndpoint(): string
    {
        return $this->cloudEnvironment->tokenEndpoint($this->tenant);
    }
}
