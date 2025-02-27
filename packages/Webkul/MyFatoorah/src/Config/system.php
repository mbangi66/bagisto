<?php

return [
    [
        'key'    => 'sales.payment_methods.myfatoorah',
        'name'   => 'MyFatoorah',
        'sort'   => 1,
        'fields' => [
            [
                'name'          => 'title',
                'title'         => 'admin::app.admin.system.title',
                'type'          => 'text',
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => true,
                'info'          => '',
            ], [
                'name'          => 'description',
                'title'         => 'admin::app.admin.system.description',
                'type'          => 'textarea',
                'channel_based' => false,
                'locale_based'  => true,
                'info'          => '',
            ], [
                'name'          => 'active',
                'title'         => 'admin::app.admin.system.status',
                'type'          => 'boolean',
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => true,
                'info'          => '',
            ], [
                'name'          => 'sandbox',
                'title'         => 'admin::app.admin.system.sandbox',
                'type'          => 'boolean',
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => true,
                'info'          => '',
            ],
            // Add a new field for the logo upload:
            [
                'name'          => 'logo',
                'title'         => 'Payment Method Logo',
                'type'          => 'image', 
                'validation'    => 'mimes:bmp,jpeg,jpg,png,webp',
                'channel_based' => false,
                'locale_based'  => false,
                'info'          => 'Upload the logo for MyFatoorah payment method.',
                'default'       => 'https://lens.majesticdemo.com/storage/configuration/dSWVR33aMWnf0CuPFnuvslz4AxzAczBIk6MlZ9QA.jpg',
            ],
        ]
    ]
];
