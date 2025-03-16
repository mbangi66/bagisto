<?php
use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Webkul\MyFatoorah\Http\Controllers'], function () {
    Route::get('payment/redirect', 'PaymentController@redirectToGateway')->name('myfatoorah.redirect');
    Route::get('payment/callback', 'PaymentController@handleGatewayCallback')->name('myfatoorah.callback.payment');
});
