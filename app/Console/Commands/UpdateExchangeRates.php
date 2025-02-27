<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Admin\Helpers\ApiLayerExchange;
use Webkul\Core\Repositories\ExchangeRateRepository;

class UpdateExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange-rates:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and update exchange rates from API Layer';

    public function __construct(
        protected ExchangeRateRepository $exchangeRateRepository,
        protected CurrencyRepository $currencyRepository,
        protected ApiLayerExchange $apiLayerExchange
    ) {
        parent::__construct();
    }

    public function handle()
    {
        try {
            // Fetch new exchange rates from API Layer
            $exchangeRates = $this->apiLayerExchange->getExchangeRates();

            if (isset($exchangeRates['error'])) {
                $this->error($exchangeRates['message']);
                return 1;
            }

            // Get all currency codes and their corresponding IDs from `lenscurrencies`
            $currencies = $this->currencyRepository->pluck('id', 'code'); 

            foreach ($exchangeRates['quotes'] as $key => $rate) {
                // Extract currency code (e.g., KWDAED -> AED)
                $targetCurrencyCode = substr($key, 3);

                if (!isset($currencies[$targetCurrencyCode])) {
                    continue; // Skip if currency does not exist
                }

                $targetCurrencyId = $currencies[$targetCurrencyCode];

                // Update or create exchange rate record
                $this->exchangeRateRepository->updateOrCreate(
                    ['target_currency' => $targetCurrencyId],
                    ['rate' => $rate]
                );
            }

            $this->info(trans('admin::app.settings.exchange-rates.index.update-success'));
        } catch (\Exception $e) {
            $this->error('Failed to update exchange rates: ' . $e->getMessage());
        }

        return 0;
    }
}
