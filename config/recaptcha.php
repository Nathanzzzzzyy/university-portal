<?php

return [
    // set RECAPTCHA_ENABLED=false in .env to disable while developing
    'enabled'  => env('RECAPTCHA_ENABLED', true),
    'site_key' => env('RECAPTCHA_SITE_KEY'),
    'secret'   => env('RECAPTCHA_SECRET_KEY'),
];