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
use Webkul\Sales\Repositories\OrderTransactionRepository;
use Webkul\Sales\Repositories\ShipmentRepository;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\Invoice;

class PaymentController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository,
        protected OrderTransactionRepository $orderTransactionRepository,
        protected ShipmentRepository $shipmentRepository,
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

        $paymentId = $request->input('paymentId') ?: $request->input('Id');
        if (!$paymentId) {
            Log::error('MyFatoorah: Payment ID missing in callback.');
            return redirect()->route('shop.checkout.cart.index')->with('error', 'Invalid callback data.');
        }

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

        $orderReference = $statusData->CustomerReference ?? null;
        if (!$orderReference) {
            Log::error('MyFatoorah: Order reference not found in payment status.', ['paymentId' => $paymentId]);
            return redirect()->route('shop.checkout.cart.index')->with('error', 'Invalid callback data.');
        }

        $order = $this->orderRepository->findOneByField(['increment_id' => $orderReference]);
        
        if (!$order) {
            Log::error('MyFatoorah: Order not found during callback.', ['order_reference' => $orderReference]);
            return redirect()->route('shop.checkout.cart.index')->with('error', 'Order not found.');
        }

        if ($this->checkDuplicateTransaction($paymentId, $order->id)) {
            return redirect()->route('shop.checkout.cart.index')
                ->with('error', 'Transaction already processed');
        }

        // Create invoice if possible
        $invoice = null;
        if ($order->canInvoice()) {
            $invoiceData = $this->prepareInvoiceData($order);
            $invoice = $this->invoiceRepository->create($invoiceData);
        }

        // Create transaction record
        $transaction = $this->createOrderTransaction($order, $invoice, $paymentId, $statusData);

        // Update order status
        $this->updateOrderAndInvoiceStatus($order, $invoice);

        Log::info('MyFatoorah: Order updated and transaction recorded.', [
            'order_id'  => $order->id,
            'paymentId' => $paymentId,
            'transaction_id' => $transaction->id
        ]);

        return redirect()->route('shop.checkout.onepage.success')->with('success', 'Payment successful!');
    }

    /**
     * Create order transaction record
     *
     * @param Order $order
     * @param Invoice|null $invoice
     * @param string $paymentId
     * @param mixed $statusData
     * @return \Webkul\Sales\Models\OrderTransaction
     */
    protected function createOrderTransaction($order, $invoice, $paymentId, $statusData)
    {
        return $this->orderTransactionRepository->create([
            'transaction_id' => $paymentId,
            'type'           => 'MyFatoorah',
            'payment_method' => 'MyFatoorah',
            'invoice_id'     => $invoice ? $invoice->id : null,
            'order_id'       => $order->id,
            'amount'         => $statusData->InvoiceValue ?? $order->grand_total,
            'status'         => 'paid',
            'data'           => json_encode([
                'paymentDetails' => $statusData,
                'paidAmount'     => $statusData->InvoiceValue ?? $order->grand_total,
            ]),
        ]);
    }

    /**
     * Update order and invoice status
     *
     * @param Order $order
     * @param Invoice|null $invoice
     */
    protected function updateOrderAndInvoiceStatus($order, $invoice)
    {
        if ($invoice) {
            // Check if invoice is fully paid
            $transactionTotal = $this->orderTransactionRepository
                ->where('invoice_id', $invoice->id)
                ->sum('amount');

            if ($transactionTotal >= $invoice->base_grand_total) {
                $shipments = $this->shipmentRepository->where('order_id', $order->id)->first();

                $status = $shipments 
                    ? Order::STATUS_COMPLETED 
                    : Order::STATUS_PROCESSING;

                $this->orderRepository->updateOrderStatus($order, $status);
                $this->invoiceRepository->updateState($invoice, Invoice::STATUS_PAID);
            }
        }
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

    protected function handlePaymentStatusRetry($paymentId, $maxRetries = 3)
    {
        $retryDelay = 5; // seconds between retries

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $mfStatus = new MyFatoorahPaymentStatus($this->mfConfig);
                $statusData = $mfStatus->getPaymentStatus($paymentId, 'PaymentId');
                
                if ($statusData) {
                    return $statusData;
                }
            } catch (Exception $ex) {
                Log::warning('MyFatoorah: Payment status retry', [
                    'attempt' => $attempt,
                    'payment_id' => $paymentId,
                    'error' => $ex->getMessage()
                ]);

                sleep($retryDelay);
            }
        }

        Log::error('MyFatoorah: Payment status retrieval failed after retries', [
            'payment_id' => $paymentId
        ]);

        return null;
    }

    protected function handlePartialPayment($order, $invoice, $statusData)
    {
        $paidAmount = $statusData->InvoiceValue ?? 0;
        $totalAmount = $order->grand_total;

        if ($paidAmount < $totalAmount) {
            Log::info('MyFatoorah: Partial payment received', [
                'paid_amount' => $paidAmount,
                'total_amount' => $totalAmount,
                'order_id' => $order->id
            ]);

            // Custom logic for partial payments
            // Potentially create a partial invoice or flag the order
            $this->orderRepository->update([
                'status' => 'partially_paid'
            ], $order->id);

            return false; // Prevent full order completion
        }
        return true;
    }

    protected function verifyPaymentAmount($order, $statusData)
    {
        $expectedAmount = $order->grand_total;
        $receivedAmount = $statusData->InvoiceValue ?? null;

        // Allow small discrepancies (e.g., currency conversion)
        $tolerance = 0.01; // 1 cent tolerance

        if (!$receivedAmount || abs($expectedAmount - $receivedAmount) > $tolerance) {
            Log::error('MyFatoorah: Payment amount mismatch', [
                'expected_amount' => $expectedAmount,
                'received_amount' => $receivedAmount,
                'order_id' => $order->id
            ]);
            return false;
        }
        return true;
    }

    // Proposed method to prevent duplicate transactions
    protected function checkDuplicateTransaction($paymentId, $orderId)
    {
        $existingTransaction = $this->orderTransactionRepository
            ->where('transaction_id', $paymentId)
            ->where('order_id', $orderId)
            ->first();

        if ($existingTransaction) {
            Log::warning('MyFatoorah: Duplicate transaction attempt', [
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'existing_transaction_id' => $existingTransaction->id
            ]);
            return true; // Duplicate found
        }
        return false;
    }

    protected function isValidPaymentStatus($statusData)
    {
        $validStatuses = ['Paid', 'Successed', 'Completed'];
        
        $paymentStatus = $statusData->InvoiceStatus ?? null;
        
        if (!$paymentStatus || !in_array($paymentStatus, $validStatuses)) {
            Log::warning('MyFatoorah: Invalid payment status', [
                'received_status' => $paymentStatus
            ]);
            return false;
        }
        return true;
    }
}
