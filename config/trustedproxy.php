<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Set trusted proxy IP addresses. Railway uses various proxy IPs,
    | so we trust all proxies for simplicity in development.
    |
    */

    'proxies' => env('TRUSTED_PROXIES') === '*' ? '*' : explode(',', env('TRUSTED_PROXIES', '')),

    /*
    |--------------------------------------------------------------------------
    | Trusted Headers
    |--------------------------------------------------------------------------
    |
    | These are the headers that your proxies use to convey information
    | about the original request. Railway typically uses these headers.
    |
    */

    'headers' =>
        Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
        Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
        Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
        Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
        Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB,

];
