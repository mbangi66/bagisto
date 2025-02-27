<?php

namespace Webkul\MyFatoorah\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Models\Order;
use MyFatoorah\Library\API\Payment\MyFatoorahPayment; // Use the direct class

class PaymentController extends Controller
{
    /**
     * Redirect to the MyFatoorah payment gateway.
     */
    public function redirectToGateway(Request $request)
    {
        \Log::info('MyFatoorah Config', [
            'api_key'      => config('myfatoora.api_key'),
            'api_url'      => config('myfatoora.api_url'),
            'callback_url' => config('myfatoora.callback_url'),
        ]);
    
        // Retrieve order_id from request or session
        $orderId = $request->input('order_id') ?: session()->get('order_id');
        Log::info('MyFatoorah: Redirect to Gateway initiated.', ['order_id' => $orderId]);
    
        // Retrieve the order details
        $order = Order::find($orderId);
        if (!$order) {
            Log::error('MyFatoorah: Order not found.', ['order_id' => $orderId]);
            return redirect()->back()->with('error', 'Order not found.');
        }
                
        // Retrieve the raw currency from the URL parameter; default to your configured country_iso
        $rawCurrency = $request->input('country_iso', config('myfatoora.country_iso')); // e.g. might be "SAU"
        
        // Define a display mapping to convert raw currency codes to proper ISO codes for DisplayCurrencyIso
        $displayMapping = [
            'KWD' => 'KWD',
            'AED' => 'AED',
            'SAR' => 'SAR',
            'BHD' => 'BHD',
            'OMR' => 'OMR',
            'QAR' => 'QAR',
        ];
        // If the raw currency comes as "SAU", we want to use "SAR" for display.
        if (strtoupper($rawCurrency) === 'SAU') {
            $displayCurrency = 'SAR';
        } else {
            $displayCurrency = isset($displayMapping[strtoupper($rawCurrency)]) 
                ? $displayMapping[strtoupper($rawCurrency)] 
                : 'KWD';
        }
        
        // Define a mapping for the configuration vcCode
        $vcMapping = [
            'KWD' => 'KWT', // Kuwaiti Dinar
            'AED' => 'ARE', // UAE Dirham
            'SAR' => 'SAU', // Saudi Riyal (vcCode)
            'BHD' => 'BHR', // Bahraini Dinar
            'OMR' => 'OMN', // Omani Rial (vcCode)
            'QAR' => 'QAT', // Qatari Riyal
        ];
        // Use the raw currency (or its uppercase version) to get the configuration value.
        // If rawCurrency comes in as "SAU", then strtoupper($rawCurrency) is "SAU".
        // Since "SAU" is not in our display mapping, we already converted it above.
        // For configuration, we want to map "SAR" to "SAU". So let's use the display currency.
        $vcCode = isset($vcMapping[$displayCurrency]) 
            ? $vcMapping[$displayCurrency] 
            : config('myfatoora.country_iso');
    
        // Prepare payment data according to MyFatoorah SendPayment endpoint requirements
        $postFields = [
            'InvoiceValue'       => $order->grand_total,
            'CustomerName'       => $order->customer_first_name . ' ' . $order->customer_last_name,
            'NotificationOption' => 'LNK', // "LNK" returns the invoice URL in the response
            'CallBackUrl'        => config('myfatoora.callback_url'),
            'ErrorUrl'           => config('myfatoora.error_url') ?? config('myfatoora.callback_url'),
            'Language'           => 'en',
            'CustomerReference'  => $order->increment_id,
            'DisplayCurrencyIso' => $displayCurrency, // Use the proper display ISO code (e.g. "SAR")
        ];
        Log::info('MyFatoorah: Payment data prepared.', $postFields);
        
        // Build MyFatoorah configuration using the mapped vcCode
        $mfConfig = [
            'apiKey'      => config('myfatoora.api_key'),
            'isTest'      => config('myfatoora.test_mode'),
            'countryCode' => $vcCode,  // e.g., for Saudi Riyal, vcCode will be "SAU"
        ];
        
        try {
            // Create an instance of the MyFatoorahPayment class
            $mfPayment = new \MyFatoorah\Library\API\Payment\MyFatoorahPayment($mfConfig);
            // Send the payment request (using the SendPayment endpoint)
            $response = $mfPayment->sendPayment($postFields);
        } catch (Exception $ex) {
            Log::error('MyFatoorah: Exception during sendPayment.', ['message' => $ex->getMessage()]);
            return redirect()->back()->with('error', 'Payment initialization failed.');
        }
        
        Log::info('MyFatoorah: API response received.', ['response' => $response]);
        
        if (isset($response->InvoiceURL)) {
            $invoiceURL = $response->InvoiceURL;
            Log::info('MyFatoorah: Redirecting to Payment URL.', ['InvoiceURL' => $invoiceURL]);
            return redirect($invoiceURL);
        }
        
        Log::error('MyFatoorah: Payment initialization failed.', ['response' => $response]);
        return redirect()->back()->with('error', 'Payment initialization failed.');
    }
    

    /**
     * Handle the callback from MyFatoorah.
     */
    public function handleGatewayCallback(Request $request)
    {
        Log::info('MyFatoorah: Callback received.', ['request' => $request->all()]);
    
        // Retrieve paymentId from callback response
        $paymentId = $request->input('paymentId') ?: $request->input('Id');
        if (!$paymentId) {
            Log::error('MyFatoorah: Payment ID missing in callback.');
            return redirect()->route('shop.checkout.cart.index')->with('error', 'Invalid callback data.');
        }
    
        // Use PaymentStatus API to get details and extract the order reference (CustomerReference)
        $mfConfig = [
            'apiKey'      => config('myfatoora.api_key'),
            'isTest'      => config('myfatoora.test_mode'),
            'countryCode' => config('myfatoora.country_iso'),
        ];
        try {
            $mfStatus = new \MyFatoorah\Library\API\Payment\MyFatoorahPaymentStatus($mfConfig);
            $statusData = $mfStatus->getPaymentStatus($paymentId, 'PaymentId');
        } catch (Exception $ex) {
            Log::error('MyFatoorah: Exception during payment status check.', ['message' => $ex->getMessage()]);
            return redirect()->route('shop.checkout.cart.index')->with('error', 'Payment verification failed.');
        }
    
        // Extract the order id (CustomerReference) from the status data
        $orderId = $statusData->CustomerReference ?? null;
        if (!$orderId) {
            Log::error('MyFatoorah: Order reference not found in payment status.', ['paymentId' => $paymentId]);
            return redirect()->route('shop.checkout.cart.index')->with('error', 'Invalid callback data.');
        }
    
        // Update the order status using Bagisto's built-in field
        $order = Order::find($orderId);
        if ($order) {
            // Set the order status to "completed" (or "processing", as per your workflow)
            $order->status = 'completed';
            $order->save();
            Log::info('MyFatoorah: Order updated to completed.', ['order_id' => $orderId, 'paymentId' => $paymentId]);
    
            // Optionally, record the transaction details in lensorder_transactions
            // This may be handled by Bagisto's Payment Repositories already,
            // otherwise you can create a new transaction record.
        } else {
            Log::error('MyFatoorah: Order not found during callback.', ['order_id' => $orderId]);
        }
    
        return redirect()->route('shop.checkout.onepage.success')->with('success', 'Payment successful!');
    }
    
}
