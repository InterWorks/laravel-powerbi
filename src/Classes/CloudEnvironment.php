<?php

namespace InterWorks\PowerBI\Classes;

/**
 * Cloud environment URL resolver for Microsoft Power BI sovereign clouds.
 *
 * Maps a cloud environment value (commercial / gcc / gcc_high / dod) to the correct Power BI
 * REST API base URL, Microsoft Entra authority, OAuth token endpoint, and resource URL.
 *
 * Defaults to commercial when an unrecognized or missing value is provided, so callers that
 * do not specify an environment continue to use the commercial Power BI service.
 *
 * References:
 * - https://learn.microsoft.com/en-us/power-bi/developer/embedded/embed-sample-for-customers-national-clouds
 * - https://learn.microsoft.com/en-us/fabric/enterprise/powerbi/service-government-us-overview
 * - https://learn.microsoft.com/en-us/entra/identity-platform/authentication-national-cloud
 */
class CloudEnvironment
{
    public const COMMERCIAL = 'commercial';

    public const GCC = 'gcc';

    public const GCC_HIGH = 'gcc_high';

    public const DOD = 'dod';

    /**
     * Returns the Power BI REST API base URL for the given cloud environment.
     *
     * @param  string  $cloudEnvironment  The cloud environment identifier.
     */
    public static function getBaseUrl(string $cloudEnvironment): string
    {
        return match (self::normalize($cloudEnvironment)) {
            self::GCC => 'https://api.powerbigov.us/v1.0/myorg',
            self::GCC_HIGH => 'https://api.high.powerbigov.us/v1.0/myorg',
            self::DOD => 'https://api.mil.powerbigov.us/v1.0/myorg',
            default => 'https://api.powerbi.com/v1.0/myorg',
        };
    }

    /**
     * Returns the OAuth authorize endpoint URL for the given cloud environment and tenant.
     *
     * Used by the Authorization Code Grant flow (PowerBIAzureUser).
     *
     * @param  string  $cloudEnvironment  The cloud environment identifier.
     * @param  string  $tenant  The Azure AD tenant ID.
     */
    public static function getAuthorizeEndpoint(string $cloudEnvironment, string $tenant): string
    {
        return self::getAuthority($cloudEnvironment)."/{$tenant}/oauth2/authorize";
    }

    /**
     * Returns the OAuth token endpoint URL for the given cloud environment and tenant.
     *
     * Used by both the Client Credentials Grant (PowerBIServicePrincipal) and the
     * Authorization Code Grant (PowerBIAzureUser) flows.
     *
     * @param  string  $cloudEnvironment  The cloud environment identifier.
     * @param  string  $tenant  The Azure AD tenant ID.
     */
    public static function getTokenEndpoint(string $cloudEnvironment, string $tenant): string
    {
        return self::getAuthority($cloudEnvironment)."/{$tenant}/oauth2/token";
    }

    /**
     * Returns the Power BI API resource URL for the given cloud environment.
     *
     * This is the OAuth resource parameter that identifies the Power BI service as the
     * target audience for the access token (required for Azure AD v1.0 token requests).
     *
     * @param  string  $cloudEnvironment  The cloud environment identifier.
     */
    public static function getResourceUrl(string $cloudEnvironment): string
    {
        return match (self::normalize($cloudEnvironment)) {
            self::GCC => 'https://analysis.usgovcloudapi.net/powerbi/api',
            self::GCC_HIGH => 'https://high.analysis.usgovcloudapi.net/powerbi/api',
            self::DOD => 'https://mil.analysis.usgovcloudapi.net/powerbi/api',
            default => 'https://analysis.windows.net/powerbi/api',
        };
    }

    /**
     * Returns the supported cloud environment identifiers.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [self::COMMERCIAL, self::GCC, self::GCC_HIGH, self::DOD];
    }

    /**
     * Returns whether the given cloud environment identifier is one of the supported values.
     *
     * @param  string  $cloudEnvironment  The cloud environment identifier.
     */
    public static function isValid(string $cloudEnvironment): bool
    {
        return in_array($cloudEnvironment, self::all(), true);
    }

    /**
     * Normalizes a possibly-missing or unrecognized cloud environment value to a supported one.
     *
     * Any unrecognized, empty, or null value normalizes to commercial.
     *
     * @param  string|null  $cloudEnvironment  The cloud environment identifier.
     */
    public static function normalize(?string $cloudEnvironment): string
    {
        if ($cloudEnvironment !== null && self::isValid($cloudEnvironment)) {
            return $cloudEnvironment;
        }

        return self::COMMERCIAL;
    }

    /**
     * Returns the Microsoft Entra authority base URL for the given cloud environment.
     *
     * Commercial uses login.microsoftonline.com; all US Government clouds share the
     * login.microsoftonline.us authority.
     *
     * @param  string  $cloudEnvironment  The cloud environment identifier.
     */
    private static function getAuthority(string $cloudEnvironment): string
    {
        return match (self::normalize($cloudEnvironment)) {
            self::GCC, self::GCC_HIGH, self::DOD => 'https://login.microsoftonline.us',
            default => 'https://login.microsoftonline.com',
        };
    }
}
