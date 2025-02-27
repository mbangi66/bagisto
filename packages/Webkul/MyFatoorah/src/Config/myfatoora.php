<?php
return [
        /**
         * API Token Key (string)
         * Accepted value:
         * Live Token: https://myfatoorah.readme.io/docs/live-token
         * Test Token: https://myfatoorah.readme.io/docs/test-token
         */
        'api_key'      => env('MYFATOORA_API_KEY', ''),
        /**
         * Test Mode (boolean)
         * Accepted value: true for the test mode or false for the live mode
         */
        'test_mode'    => env('MYFATOORA_TEST_MODE', true),
        /**
         * Country ISO Code (string)
         * Accepted value: KWT, SAU, ARE, QAT, BHR, OMN, JOD, or EGY.
         */
       'country_iso'  => env('MYFATOORA_COUNTRY_ISO', 'KWT'),
        /**
         * Save card (boolean)
         * Accepted value: true if you want to enable save card options.
         * You should contact your account manager to enable this feature in your MyFatoorah account as well.
         */
        'save_card' => true,
        /**
         * Webhook secret key (string)
         * Enable webhook on your MyFatoorah account setting then paste the secret key here.
         * The webhook link is: https://{example.com}/myfatoorah/webhook
         */
        'webhook_secret_key' => '',
        /**
         * Register Apple Pay (boolean)
         * Set it to true to show the Apple Pay on the checkout page.
         * First, verify your domain with Apple Pay before you set it to true.
         * You can either follow the steps here: https://docs.myfatoorah.com/docs/apple-pay#verify-your-domain-with-apple-pay or contact the MyFatoorah support team (tech@myfatoorah.com).
        */
        'register_apple_pay' => false,
    
        'error_url'    => env('MYFATOORA_ERROR_URL', env('MYFATOORA_CALLBACK_URL')),
    
        'callback_url' => env('MYFATOORA_CALLBACK_URL', ''),
    
        'api_url'      => env('MYFATOORA_API_URL', 'https://apitest.myfatoorah.com/v2'),  
];
