<?php

// config for InterWorks/PowerBI
return [
    /*
    |--------------------------------------------------------------------------
    | Azure AD Tenant ID
    |--------------------------------------------------------------------------
    |
    | The Azure Active Directory tenant ID where your Power BI application
    | is registered. This is used for all authentication flows.
    |
    */
    'tenant' => env('POWER_BI_TENANT', ''),

    /*
    |--------------------------------------------------------------------------
    | Service Principal Credentials (Client Credentials Grant)
    |--------------------------------------------------------------------------
    |
    | Credentials for standard Service Principal authentication. This uses
    | OAuth 2.0 Client Credentials Grant for server-to-server API access
    | without user interaction. Use with PowerBI::forServicePrincipal().
    |
    */
    'client_id' => env('POWER_BI_CLIENT_ID', ''),
    'client_secret' => env('POWER_BI_CLIENT_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Admin Service Principal Credentials (Client Credentials Grant)
    |--------------------------------------------------------------------------
    |
    | Credentials for Admin Service Principal with Power BI administrator
    | rights. Required for accessing admin endpoints (/admin/*).
    | Use with PowerBI::forAdminServicePrincipal().
    |
    */
    'admin_client_id' => env('POWER_BI_ADMIN_CLIENT_ID', ''),
    'admin_client_secret' => env('POWER_BI_ADMIN_CLIENT_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Azure User OAuth Redirect URI (Authorization Code Grant)
    |--------------------------------------------------------------------------
    |
    | Redirect URI for Azure User authentication. Azure User authentication
    | uses the same client_id and client_secret as the standard Service
    | Principal, but with Authorization Code Grant flow requiring browser-based
    | user authentication. Use with PowerBI::azureUser().
    |
    */
    'redirect_uri' => env('POWER_BI_REDIRECT_URI', ''),

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Configure API response caching behavior. Caching uses Laravel's default
    | cache store and is recommended for production to reduce API calls.
    |
    */
    'cache' => [
        /*
        | Enable or disable caching for Power BI API responses.
        | When disabled, all requests bypass cache.
        */
        'enabled' => env('POWER_BI_CACHE_ENABLED', true),

        /*
        | Cache expiry time in seconds. Must be an integer >= 1.
        | Default: 3600 (1 hour)
        |
        | This value cannot be 0 or null. To disable caching, set 'enabled' to false.
        */
        'expiry_seconds' => env('POWER_BI_CACHE_EXPIRY_SECONDS', 3600),
    ],
];
