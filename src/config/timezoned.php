<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default User Timezone Resolver
    |--------------------------------------------------------------------------
    |
    | This callback returns the timezone that should be used for converting
    | model date attributes. It receives the current model instance.
    |
    | You can change this to your own logic, e.g. pulling from the user model,
    | session, request, or a global app setting.
    |
    */

    'timezone_resolver' => function ($model) {
        return auth()->user()?->timezone ?? 'UTC';
    },

    /*|--------------------------------------------------------------------------
    | Database Timezone
    |--------------------------------------------------------------------------
    |
    | The timezone that your database stores dates in.
    |
    */

    'database_timezone' => 'UTC',

];