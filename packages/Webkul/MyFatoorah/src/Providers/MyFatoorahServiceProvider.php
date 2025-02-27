<?php

namespace Webkul\MyFatoorah\Providers;

use Illuminate\Support\ServiceProvider;

class MyFatoorahServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__.'/../Http/routes.php';
        
        // Load views if you have any:
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'myfatoorah');

        // Load translations if any:
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'myfatoorah');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();
    }
    
    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/paymentmethods.php', 'payment_methods'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/system.php', 'core'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../Config/myfatoora.php', 'myfatoora'
        );
        
    }
}
