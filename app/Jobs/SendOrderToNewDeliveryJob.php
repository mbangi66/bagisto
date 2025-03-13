<?php

namespace App\Jobs;

use Webkul\Sales\Models\Order; 
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderToNewDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    protected $lang;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\Order $order
     * @param string $lang
     */
    public function __construct(Order $order, $lang = 'ar')
    {
        $this->order = $order;
        $this->lang = $lang;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Call the method on the order to send to the new delivery system.
        // Adjust method name if needed.
        $this->order->sendToNewDeliverySystem($this->lang);
    }
}
