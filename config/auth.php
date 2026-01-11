<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication "guard" and password
    | reset options for your application. You may change these defaults
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | here which uses session storage and the Eloquent user provider.
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'qtap_admins' => [
            'driver' => 'jwt',
            'provider' => 'qtap_admins', // يحدد المزود المرتبط بهذا الحارس
        ],
        'qtap_affiliate' => [
            'driver' => 'jwt',
            'provider' => 'qtap_affiliate', // يحدد المزود المرتبط بهذا الحارس
        ],
        'qtap_clients' => [
            'driver' => 'jwt',
            'provider' => 'qtap_clients', // يحدد المزود المرتبط بهذا الحارس
        ],



        'restaurant_user_staff' => [
            'driver' => 'jwt', // استخدام jwt كـ driver
            'provider' => 'restaurant_user_staff', // استخدام المزود المعرف أدناه
        ],


        'delivery_rider' => [
            'driver' => 'jwt', // استخدام jwt كـ driver
            'provider' => 'delivery_rider', // استخدام المزود المعرف أدناه
        ],




        'api' => [
            'driver' => 'jwt',
            'provider' => 'users', // يحدد المزود المرتبط بهذا الحارس
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | If you have multiple user tables or models you may configure multiple
    | sources which represent each model / table. These sources may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
        'qtap_admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\qtap_admins::class,
        ],
        'qtap_clients' => [
            'driver' => 'eloquent',
            'model' => App\Models\qtap_clients::class,
        ],
        'qtap_affiliate' => [
            'driver' => 'eloquent',
            'model' => App\Models\qtap_affiliate::class,
        ],

        'restaurant_user_staff' => [
            'driver' => 'eloquent', // استخدام Eloquent كمزود البيانات
            'model' => App\Models\restaurant_user_staff::class, // تحديد النموذج المرتبط
        ],


        'delivery_rider' => [
            'driver' => 'eloquent', // استخدام Eloquent كمزود البيانات
            'model' => App\Models\delivery_rider::class, // تحديد النموذج المرتبط
        ],




        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | You may specify multiple password reset configurations if you have more
    | than one user table or model in the application and you want to have
    | separate password reset settings based on the specific user types.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | The throttle setting is the number of seconds a user must wait before
    | generating more password reset tokens. This prevents the user from
    | quickly generating a very large amount of password reset tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the amount of seconds before a password confirmation
    | times out and the user is prompted to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => 10800,

];
