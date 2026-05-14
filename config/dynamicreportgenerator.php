<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reportable Models
    |--------------------------------------------------------------------------
    |
    | Only the Eloquent models listed here are permitted to be used within
    | the dynamic reporting engine. Models must extend Illuminate\Database\Eloquent\Model.
    |
    */
    'reportable_models' => [
        \App\Models\User::class,
        \App\Models\Role::class,
        \App\Models\Product::class,
        \App\Models\Order::class,
        \App\Models\OrderItem::class,
        \App\Models\Cart::class,
        \App\Models\CartItem::class,
        \App\Models\Wishlist::class,
        \App\Models\WishlistItem::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Execution Limits
    |--------------------------------------------------------------------------
    |
    | Limits applied to the execution of generated reports.
    |
    */
    'limits' => [
        'max_rows' => 5000,
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Configuration (Optional)
    |--------------------------------------------------------------------------
    |
    | If enabled, the package will register its optional API endpoints.
    |
    */
    'http' => [
        'enabled' => false,
        'prefix' => 'dynamic-reporting',
        'middleware' => ['web', 'auth'],
    ],
];
