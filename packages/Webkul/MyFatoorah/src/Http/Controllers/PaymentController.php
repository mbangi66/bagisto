<?php

namespace Webkul\MyFatoorah\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use MyFatoorah\Library\API\Payment\MyFatoorahPayment;
use MyFatoorah\Library\API\Payment\MyFatoorahPaymentStatus;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\InvoiceRepository;

class PaymentController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository
    ) {}

    /**
     * Redirect to the MyFatoorah payment gateway.
     */
    public function redirectToGateway(Request $request)
    {
        Log::info('MyFatoorah Config', [
            'api_key'      => config('myfatoora.api_key'),
            'api_url'      => config('myfatoora.api_url'),
            'callback_url' => config('myfatoora.callback_url'),
        ]);
    
        // Retrieve order_id from request or session.
        $orderId = $request->input('order_id') ?: session()->get('order_id');
        Log::info('MyFatoorah: Redirect to Gateway initiated.', ['order_id' => $orderId]);
    
        // Retrieve the order details using the repository.
        $order = $this->orderRepository->find($orderId);
        if (!$order) {
            Log::error('MyFatoorah: Order not found.', ['order_id' => $orderId]);
            return redirect()->back()->with('error', 'Order not found.');
        }
                
        // Retrieve the raw currency from the URL parameter; default to the configured country_iso.
        $rawCurrency = $request->input('country_iso', config('myfatoora.country_iso'));
        
        // Define display mapping for proper ISO codes.
        $displayMapping = [
            'KWD' => 'KWD',
            'AED' => 'AED',
            'SAR' => 'SAR',
            'BHD' => 'BHD',
            'OMR' => 'OMR',
            'QAR' => 'QAR',
        ];
        // Convert "SAU" to "SAR" for display.
        if (strtoupper($rawCurrency) === 'SAU') {
            $displayCurrency = 'SAR';
        } else {
            $displayCurrency = isset($displayMapping[strtoupper($rawCurrency)])
                ? $displayMapping[strtoupper($rawCurrency)]
                : 'KWD';
        }
        
        // Define configuration mapping (vcCode).
        $vcMapping = [
            'KWD' => 'KWT', // Kuwaiti Dinar.
            'AED' => 'ARE', // UAE Dirham.
            'SAR' => 'SAU', // Saudi Riyal (vcCode).
            'BHD' => 'BHR', // Bahraini Dinar.
            'OMR' => 'OMN', // Omani Rial (vcCode).
            'QAR' => 'QAT', // Qatari Riyal.
        ];
        $vcCode = isset($vcMapping[$displayCurrency])
            ? $vcMapping[$displayCurrency]
            : config('myfatoora.country_iso');
    
        // Prepare payment data for MyFatoorah.
        $postFields = [
            'InvoiceValue'       => $order->grand_total,
            'CustomerName'       => $order->customer_first_name . ' ' . $order->customer_last_name,
            'NotificationOption' => 'LNK', // "LNK" returns the invoice URL in the response.
            'CallBackUrl'        => config('myfatoora.callback_url'),
            'ErrorUrl'           => config('myfatoora.error_url') ?? config('myfatoora.callback_url'),
            'Language'           => 'en',
            // Use the order's increment_id as CustomerReference.
            'CustomerReference'  => $order->increment_id,
            'DisplayCurrencyIso' => $displayCurrency,
        ];
        Log::info('MyFatoorah: Payment data prepared.', $postFields);
        
        // Build MyFatoorah configuration.
        $mfConfig = [
            'apiKey'      => config('myfatoora.api_key'),
            'isTest'      => config('myfatoora.test_mode'),
            'countryCode' => $vcCode,
        ];
        
        try {
            // Send the payment request.
            $mfPayment = new MyFatoorahPayment($mfConfig);
            $response = $mfPayment->sendPayment($postFields);
        } catch (Exception $ex) {
            Log::error('MyFatoorah: Exception during sendPayment.', ['message' => $ex->getMessage()]);
            return redirect()->back()->with('error', 'Payment initialization failed.');
        }
        
        Log::info('MyFatoorah: API response received.', ['response' => $response]);
        
        if (isset($response->InvoiceURL)) {
            Log::info('MyFatoorah: Redirecting to Payment URL.', ['InvoiceURL' => $response->InvoiceURL]);
            return redirect($response->InvoiceURL);
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
    
        // Retrieve paymentId from the callback response.
        $paymentId = $request->input('paymentId') ?: $request->input('Id');
        if (!$paymentId) {
            Log::error('MyFatoorah: Payment ID missing in callback.');
            return redirect()->route('shop.checkout.cart.index')->with('error', 'Invalid callback data.');
        }
    
        // Use PaymentStatus API to get details and extract the order reference.
        $mfConfig = [
            'apiKey'      => config('myfatoora.api_key'),
            'isTest'      => config('myfatoora.test_mode'),
            'countryCode' => config('myfatoora.country_iso'),
        ];
        try {
            $mfStatus = new MyFatoorahPaymentStatus($mfConfig);
            $statusData = $mfStatus->getPaymentStatus($paymentId, 'PaymentId');
        } catch (Exception $ex) {
            Log::error('MyFatoorah: Exception during payment status check.', ['message' => $ex->getMessage()]);
            return redirect()->route('shop.checkout.cart.index')->with('error', 'Payment verification failed.');
        }
    
        // Extract the order reference from the status data.
        $orderReference = $statusData->CustomerReference ?? null;
        if (!$orderReference) {
            Log::error('MyFatoorah: Order reference not found in payment status.', ['paymentId' => $paymentId]);
            return redirect()->route('shop.checkout.cart.index')->with('error', 'Invalid callback data.');
        }
    
        // Find the order using the increment_id.
        $order = $this->orderRepository->findOneByField(['increment_id' => $orderReference]);
        if ($order) {
            // Update the order status.
            $this->orderRepository->update(['status' => 'processing'], $order->id);
    
            if ($order->canInvoice()) {
                $invoiceData = $this->prepareInvoiceData($order);
                $invoice = $this->invoiceRepository->create($invoiceData);
            }
    
            Log::info('MyFatoorah: Order updated and invoice created if applicable.', [
                'order_id'  => $order->id,
                'paymentId' => $paymentId
            ]);
        } else {
            Log::error('MyFatoorah: Order not found during callback.', ['order_reference' => $orderReference]);
        }
    
        return redirect()->route('shop.checkout.onepage.success')->with('success', 'Payment successful!');
    }
    
    /**
     * Prepare invoice data for an order.
     *
     * @param  \Webkul\Sales\Models\Order  $order
     * @return array
     */
    protected function prepareInvoiceData($order)
    {
        $invoiceData = ['order_id' => $order->id];
    
        foreach ($order->items as $item) {
            // Ensure that qty_to_invoice holds the correct invoicable quantity.
            $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
        }
    
        return $invoiceData;
    }
}
