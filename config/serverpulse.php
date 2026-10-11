<?php

return [

    /*
    |--------------------------------------------------------------------------
    | codeorspulse API Configuration
    |--------------------------------------------------------------------------
    |
    | These values identify your codeorspulse installation. The api_base is
    | the root URL of your codeorspulse API and api_key is your unique
    | agent authentication key.
    |
    */

    'api_base' => env('codeorspulse_API_BASE', 'https://codeorspulse.coder71.com'),

    'api_key' => env('codeorspulse_API_KEY'),

];
