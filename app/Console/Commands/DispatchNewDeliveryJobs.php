<?php

namespace App\Console\Commands;

use Webkul\Sales\Models\Order;
use App\Jobs\SendOrderToNewDeliveryJob;
use Illuminate\Console\Command;

class DispatchNewDeliveryJobs extends Command
{
    protected $signature = 'orders:dispatch-new-delivery';
    protected $description = 'Dispatch jobs to send orders to the new delivery system';

    public function handle()
    {
        // Example: select orders that have not yet been sent (customize the query as needed)
        $orders = Order::whereNull('sent_to_delivery')
                        ->where('status', '=', 'processing')
                        ->get();

        foreach ($orders as $order) {
            SendOrderToNewDeliveryJob::dispatch($order, 'ar');
        }

        $this->info('Dispatched jobs for ' . $orders->count() . ' orders.');
    }
}
