<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Microsoft Graph Configuration (Client Credentials Flow)
    |--------------------------------------------------------------------------
    |
    | Configuration for Microsoft Graph API integration for sending emails
    | using Client Credentials OAuth 2.0 flow (no user interaction needed)
    |
    */

    'client_id' => env('MSGRAPH_CLIENT_ID'),
    'client_secret' => env('MSGRAPH_CLIENT_SECRET'),
    'tenant_id' => env('MSGRAPH_TENANT_ID'),
    'user_email' => env('MSGRAPH_USER_EMAIL'),
    
    /*
    |--------------------------------------------------------------------------
    | Endpoints
    |--------------------------------------------------------------------------
    */
    'authority' => 'https://login.microsoftonline.com',
    'graph_url' => 'https://graph.microsoft.com/v1.0',

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache_token' => true,
    'cache_key' => 'msgraph_access_token',
];
