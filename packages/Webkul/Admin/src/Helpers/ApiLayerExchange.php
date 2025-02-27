<?php

namespace Webkul\Admin\Helpers;

use Illuminate\Support\Facades\Http;

class ApiLayerExchange
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.exchange_api.apilayer.key');
        $this->apiUrl = config('services.exchange_api.apilayer.url');
    }

    /**
     * Fetch exchange rates with KWD as the source currency.
     */
    public function getExchangeRates()
    {
        $currencies = 'AED,SAR,QAR,OMR,BHD'; // Gulf currencies
        $source = 'KWD';

        // Construct API URL
        $url = "{$this->apiUrl}?access_key={$this->apiKey}&currencies={$currencies}&source={$source}&format=1";

        // Call the API using Laravel's HTTP client
        $response = Http::get($url);

        // Check if API call is successful
        if ($response->successful()) {
            return $response->json();
        }

        // Return an error if the API call fails
        return [
            'error' => 'Failed to fetch exchange rates.',
            'message' => $response->body(),
        ];
    }
}
