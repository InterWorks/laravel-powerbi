<?php

namespace InterWorks\PowerBI\Enums;

use InvalidArgumentException;

/**
 * Microsoft cloud environments supported by Power BI, including US Government sovereign clouds.
 *
 * Each case resolves to the correct Power BI REST API base URL, Microsoft Entra authority,
 * OAuth authorize/token endpoints, and resource URL for that cloud.
 *
 * References:
 * - https://learn.microsoft.com/en-us/power-bi/developer/embedded/embed-sample-for-customers-national-clouds
 * - https://learn.microsoft.com/en-us/fabric/enterprise/powerbi/service-government-us-overview
 * - https://learn.microsoft.com/en-us/entra/identity-platform/authentication-national-cloud
 */
enum CloudEnvironment: string
{
    case Commercial = 'commercial';
    case GCC = 'gcc';
    case GCCHigh = 'gcc_high';
    case DoD = 'dod';

    /**
     * Resolves a cloud environment from a config or user-supplied string.
     *
     * A null or empty value resolves to Commercial so callers that do not specify an
     * environment continue to use the commercial Power BI service. Any other
     * unrecognized value throws, so a typo (e.g. 'gcc-high') can never silently
     * route a sovereign-cloud tenant to the commercial endpoints.
     *
     * @param  string|null  $value  The cloud environment identifier.
     *
     * @throws InvalidArgumentException When the value is not a supported cloud environment
     */
    public static function fromString(?string $value): self
    {
        if ($value === null || $value === '') {
            return self::Commercial;
        }

        return self::tryFrom($value) ?? throw new InvalidArgumentException(
            "Invalid Power BI cloud environment: {$value}. ".
            'Supported values: '.implode(', ', array_column(self::cases(), 'value')).'.'
        );
    }

    /**
     * Returns the Power BI REST API base URL for this cloud environment.
     */
    public function baseUrl(): string
    {
        return match ($this) {
            self::Commercial => 'https://api.powerbi.com/v1.0/myorg',
            self::GCC => 'https://api.powerbigov.us/v1.0/myorg',
            self::GCCHigh => 'https://api.high.powerbigov.us/v1.0/myorg',
            self::DoD => 'https://api.mil.powerbigov.us/v1.0/myorg',
        };
    }

    /**
     * Returns the OAuth authorize endpoint URL for this cloud environment and tenant.
     *
     * Used by the Authorization Code Grant flow (PowerBIAzureUser).
     *
     * @param  string  $tenant  The Azure AD tenant ID.
     */
    public function authorizeEndpoint(string $tenant): string
    {
        return $this->authority()."/{$tenant}/oauth2/authorize";
    }

    /**
     * Returns the OAuth token endpoint URL for this cloud environment and tenant.
     *
     * Used by both the Client Credentials Grant (PowerBIServicePrincipal) and the
     * Authorization Code Grant (PowerBIAzureUser) flows.
     *
     * @param  string  $tenant  The Azure AD tenant ID.
     */
    public function tokenEndpoint(string $tenant): string
    {
        return $this->authority()."/{$tenant}/oauth2/token";
    }

    /**
     * Returns the Power BI API resource URL for this cloud environment.
     *
     * This is the OAuth resource parameter that identifies the Power BI service as the
     * target audience for the access token (required for Azure AD v1.0 token requests).
     */
    public function resourceUrl(): string
    {
        return match ($this) {
            self::Commercial => 'https://analysis.windows.net/powerbi/api',
            self::GCC => 'https://analysis.usgovcloudapi.net/powerbi/api',
            self::GCCHigh => 'https://high.analysis.usgovcloudapi.net/powerbi/api',
            self::DoD => 'https://mil.analysis.usgovcloudapi.net/powerbi/api',
        };
    }

    /**
     * Returns the Microsoft Entra authority base URL for this cloud environment.
     *
     * GCC (moderate) identities live in commercial Microsoft Entra, so it authenticates
     * against login.microsoftonline.com — the same authority as Commercial. Only GCC High
     * and DoD use the Azure Government authority, login.microsoftonline.us.
     */
    private function authority(): string
    {
        return match ($this) {
            self::Commercial, self::GCC => 'https://login.microsoftonline.com',
            self::GCCHigh, self::DoD => 'https://login.microsoftonline.us',
        };
    }
}
