<?php

namespace Webkul\MyFatoorah\Payment;

use Webkul\Payment\Payment\Payment;

class MyFatoorah extends Payment
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $code  = 'myfatoorah';

    public function getRedirectUrl()
    {
        return route('myfatoorah.redirect');
    }

    public function getImage()
    {
        // Either return a hard-coded URL, or fetch it from configuration:
        return config('payment_methods.myfatoorah.logo');
    }
}