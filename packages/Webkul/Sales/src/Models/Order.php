<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Webkul\Checkout\Models\CartProxy;
use Webkul\Sales\Contracts\Order as OrderContract;
use Webkul\Sales\Database\Factories\OrderFactory;

class Order extends Model implements OrderContract
{
    use HasFactory;

    protected $dates = ['created_at'];

    protected $appends = ['datetime'];

    /**
     * Pending Order
     */
    public const STATUS_PENDING = 'pending';

    /**
     * Payment is in pending
     */
    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    /**
     * Order in processing
     */
    public const STATUS_PROCESSING = 'processing';

    /**
     * Complete Order
     */
    public const STATUS_COMPLETED = 'completed';

    /**
     * Canceled Order
     */
    public const STATUS_CANCELED = 'canceled';

    /**
     * Closed Order
     */
    public const STATUS_CLOSED = 'closed';

    /**
     * Fraud Order
     */
    public const STATUS_FRAUD = 'fraud';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'items',
        'shipping_address',
        'billing_address',
        'customer',
        'channel',
        'payment',
        'created_at',
        'updated_at',
    ];

    protected $statusLabel = [
        self::STATUS_PENDING         => 'Pending',
        self::STATUS_PENDING_PAYMENT => 'Pending Payment',
        self::STATUS_PROCESSING      => 'Processing',
        self::STATUS_COMPLETED       => 'Completed',
        self::STATUS_CANCELED        => 'Canceled',
        self::STATUS_CLOSED          => 'Closed',
        self::STATUS_FRAUD           => 'Fraud',
    ];

    /**
     * Get the order items record associated with the order.
     */
    public function getCustomerFullNameAttribute(): string
    {
        return $this->customer_first_name.' '.$this->customer_last_name;
    }

    /**
     * Returns the status label from status code
     */
    public function getStatusLabelAttribute()
    {
        return $this->statusLabel[$this->status];
    }

    /**
     * Return base total due amount
     */
    public function getBaseTotalDueAttribute()
    {
        return $this->base_grand_total - $this->base_grand_total_invoiced;
    }

    /**
     * Return total due amount
     */
    public function getTotalDueAttribute()
    {
        return $this->grand_total - $this->grand_total_invoiced;
    }

    /**
     * Return Human Friendly Date
     */
    public function getDatetimeAttribute()
    {
        return $this->created_at?->diffForHumans();
    }

    /**
     * Get the associated cart that was used to create this order.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(CartProxy::modelClass());
    }

    /**
     * Get the order items record associated with the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItemProxy::modelClass())
            ->whereNull('parent_id');
    }

    /**
     * Get the comments record associated with the order.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(OrderCommentProxy::modelClass());
    }

    /**
     * Get the order items record associated with the order.
     */
    public function all_items(): HasMany
    {
        return $this->hasMany(OrderItemProxy::modelClass());
    }

    /**
     * Get the order record associated with the item.
     */
    public function downloadable_link_purchased()
    {
        return $this->hasMany(DownloadableLinkPurchasedProxy::modelClass());
    }

    /**
     * Get the order shipments record associated with the order.
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(ShipmentProxy::modelClass());
    }

    /**
     * Get the order invoices record associated with the order.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(InvoiceProxy::modelClass());
    }

    /**
     * Get the order refunds record associated with the order.
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(RefundProxy::modelClass());
    }

    /**
     * Get the order transactions record associated with the order.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(OrderTransactionProxy::modelClass());
    }

    /**
     * Get the customer record associated with the order.
     */
    public function customer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the addresses for the order.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(OrderAddressProxy::modelClass());
    }

    /**
     * Get the payment for the order.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(OrderPaymentProxy::modelClass());
    }

    /**
     * Get the billing address for the order.
     */
    public function billing_address()
    {
        return $this->addresses
            ->where('address_type', OrderAddress::ADDRESS_TYPE_BILLING);
    }

    /**
     * Get billing address for the order.
     */
    public function getBillingAddressAttribute()
    {
        return $this->billing_address()
            ->first();
    }

    /**
     * Get the shipping address for the order.
     */
    public function shipping_address()
    {
        return $this->addresses
            ->where('address_type', OrderAddress::ADDRESS_TYPE_SHIPPING);
    }

    /**
     * Get shipping address for the order.
     */
    public function getShippingAddressAttribute()
    {
        return $this->shipping_address()
            ->first();
    }

    /**
     * Get the channel record associated with the order.
     */
    public function channel()
    {
        return $this->morphTo();
    }

    /**
     * Checks if cart have stockable items
     */
    public function haveStockableItems(): bool
    {
        foreach ($this->items as $item) {
            if ($item->getTypeInstance()->isStockable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if new shipment is allow or not
     */
    public function canShip(): bool
    {
        foreach ($this->items as $item) {
            if (
                $item->canShip()
                && ! in_array($item->order->status, [
                    self::STATUS_CLOSED,
                    self::STATUS_FRAUD,
                ])
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if new invoice is allow or not
     */
    public function canInvoice(): bool
    {
        foreach ($this->items as $item) {
            if (
                $item->canInvoice()
                && ! in_array($item->order->status, [
                    self::STATUS_CLOSED,
                    self::STATUS_FRAUD,
                ])
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify if a invoice is still unpaid
     */
    public function hasOpenInvoice(): bool
    {
        $pendingInvoice = $this->invoices()->where('state', 'pending')
            ->orWhere('state', 'pending_payment')
            ->first();

        if ($pendingInvoice) {
            return true;
        }

        return false;
    }

    /**
     * Checks if order can be canceled or not
     */
    public function canCancel(): bool
    {
        $pendingInvoice = $this->invoices->where('state', 'pending')->first();

        if ($pendingInvoice) {
            return true;
        }

        foreach ($this->items as $item) {
            if (
                $item->canCancel()
                && ! in_array($item->order->status, [
                    self::STATUS_CLOSED,
                    self::STATUS_FRAUD,
                ])
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if order can be refunded or not
     */
    public function canRefund(): bool
    {
        foreach ($this->items as $item) {
            if (
                $item->qty_to_refund > 0
                && ! in_array($item->order->status, [
                    self::STATUS_CLOSED,
                    self::STATUS_FRAUD,
                ])
            ) {
                return true;
            }
        }

        if ($this->base_grand_total_invoiced - $this->base_grand_total_refunded - $this->refunds()->sum('base_adjustment_fee') > 0) {
            return true;
        }

        return false;
    }

    /**
     * Checks if order can be reorder or not
     */
    public function canReorder(): bool
    {
        if ($this->is_guest) {
            return false;
        }

        foreach ($this->items as $item) {
            if (! $item->product?->getTypeInstance()->isSaleable()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return OrderFactory::new();
    }

    public function sendToNewDeliverySystem($lang = 'ar')
    {
        info("sendToNewDeliverySystem started for order #{$this->id}.");
    
        // Check if the new delivery system is enabled via .env
        if (!env('NEW_DELIVERY_ENABLED', false)) {
            info("New Delivery System is disabled via .env.");
            return false;
        }
    
        // Retrieve settings from .env and config files.
        $settings = [
            'userName'  => env('NEW_DELIVERY_USERNAME'),
            'password'  => env('NEW_DELIVERY_PASSWORD'),
            'url_token' => config('services.new_delivery.url_token'),
            'url_user'  => config('services.new_delivery.url_user'),
            'url_order' => config('services.new_delivery.url_order'),
        ];
        info("New Delivery settings loaded.", $settings);
    
        // Validate required configuration
        if (!$settings['userName'] || !$settings['password'] || !$settings['url_token'] ||
            !$settings['url_user'] || !$settings['url_order']) {
            info('New Delivery System configuration missing');
            return false;
        }
    
        // Skip orders already marked as processed.
        if ($this->status == 'completed') {
            info('Skipped order #' . $this->id . ' already processing.');
            return false;
        }
    
        // Retrieve shipping address record to build full address.
        $addressRecord = \DB::table('addresses')
            ->where('order_id', $this->id)
            ->where('address_type', 'order_shipping')
            ->first();
    
        if (!$addressRecord) {
            info("Shipping address not found, checking billing address.");
            $addressRecord = \DB::table('addresses')
                ->where('order_id', $this->id)
                ->where('address_type', 'order_billing')
                ->first();
        }
    
        // Get block name based on block_id and language.
        $blockName = '';
        if ($addressRecord && !empty($addressRecord->block_id)) {
            $block = \DB::table('blocks')->where('id', $addressRecord->block_id)->first();
            if ($block) {
                $blockName = ($lang === 'ar') ? $block->name_ar : $block->name_en;
            }
        }
    
        // Build full delivery address: street, city, block name, state.
        $deliveryAddress = $addressRecord
            ? trim($addressRecord->address) . "\n" .
              trim($addressRecord->city) . "\n" .
              trim($blockName) . "\n" .
              trim($addressRecord->state)
            : '';
    
        // Append a random 4-digit number to the order number.
        $orderNumber = $this->increment_id . '-' . rand(1000, 9999);
    
        // Map order data.
        $customerFullName = trim($this->customer_first_name . ' ' . $this->customer_last_name);
        info("Order mapping completed.", [
            'customerFullName' => $customerFullName,
            'orderNumber'      => $orderNumber,
            'deliveryAddress'  => $deliveryAddress,
        ]);
    
        // Retrieve phone number from the address record.
        $phone = $addressRecord && !empty($addressRecord->phone)
            ? $addressRecord->phone
            : '0000000000';
        info("Phone number determined.", ['phone' => $phone]);
    
        $paymentMethod = $this->payment_method ?? 'COD';
    
        // Retrieve block coordinates based on block_id.
        $deliveryLat = 48.1249; // default
        $deliveryLng = 29.125;  // default
        if ($addressRecord && !empty($addressRecord->block_id)) {
            $block = \DB::table('blocks')->where('id', $addressRecord->block_id)->first();
            if ($block) {
                $deliveryLat = $block->lat ?? $deliveryLat;
                $deliveryLng = $block->lng ?? $deliveryLng;
            }
        }
        info("Block coordinates determined.", ['lat' => $deliveryLat, 'lng' => $deliveryLng]);
    
        // Authenticate with the new delivery system to get a token.
        info("Requesting token from new delivery system.");
        $response = \Illuminate\Support\Facades\Http::withHeaders(['Content-Type' => 'application/json'])
            ->send('POST', $settings['url_token'], [
                'body' => json_encode([
                    'userName' => $settings['userName'],
                    'password' => $settings['password'],
                ]),
            ])->json();
        info("Token response received.", $response);
    
        if (\Arr::get($response, 'message') !== 'Success') {
            info("Token error: ", $response);
            return 'Token error';
        }
    
        // Retrieve user info using the token.
        info("Requesting user information with obtained token.");
        $responseUser = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $response['token'],
                'Content-Type'  => 'application/json'
            ])
            ->send('POST', $settings['url_user'], [
                'body' => json_encode(['userName' => $settings['userName']]),
            ])->json();
        info("User info response received.", $responseUser);
    
        if (\Arr::get($responseUser, 'status.isSuccess') !== true) {
            info("User info error: ", $responseUser);
            return 'User error';
        }
    
        // Prepare order items array as lstTalabatItem.
        $lstTalabatItem = [];
        foreach ($this->items as $item) {
            $lstTalabatItem[] = [
                'itemname'  => $item->name,
                'unitprice' => (string) $item->price,
                'quantity'  => $item->qty_ordered,
                'comment'   => '', // Populate if any item notes exist.
            ];
        }
        info("Order items prepared.", ['lstTalabatItem' => $lstTalabatItem]);
    
        // Prepare the payload for the delivery system matching the restaurant API structure.
        $data = [
            'companyId'           => (string) $responseUser['dataValue']['companyId'],
            'userId'              => (string) $responseUser['dataValue']['userId'],
            'orderNo'             => $orderNumber,
            'orderDate'           => date(DATE_ATOM),
            'orderAmount'         => (string) $this->grand_total,
            'DeliveryCustomerName'=> $customerFullName,
            'DeliveryCustomerMobile'=> $phone,
            'DeliveryAddress'     => $deliveryAddress,
            'DeliveryCity'        => $this->shipping_description ?? '',
            'DeliveryPostalCode'  => $this->coupon_code ?? '',
            'PaymentMethod'       => (string) $paymentMethod,
            'deliveryLat'         => (string) $deliveryLat,
            'deliveryLng'         => (string) $deliveryLng,
            'lstTalabatItem'      => $lstTalabatItem,
        ];
        info("Payload for order submission prepared.", $data);
    
        // For testing, log the payload and send to API.
        info("Final payload for new delivery system:", $data);
    
        info("Sending order data to the new delivery system.");
        $responseOrder = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $response['token'],
                'Content-Type'  => 'application/json'
            ])
            ->send('POST', $settings['url_order'], [
                'body' => json_encode($data),
            ])->json();
        info("Order response received.", $responseOrder);
    
        if (\Arr::get($responseOrder, 'status.isSuccess') !== true) {
            if (\Arr::get($responseOrder, 'status.message') !== 'Already exists') {
                info("Order submission error: ", $responseOrder);
                return \Arr::get($responseOrder, 'status.message');
            }
        }
    
        // Mark the order as sent to the delivery system.
        $this->sent_to_delivery = now();
        $this->save();
        info("Order #{$this->id} marked as sent to the delivery system.");
    
        return true;
    }
    
    
}
