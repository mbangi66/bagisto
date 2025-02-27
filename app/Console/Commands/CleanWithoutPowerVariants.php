<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\Log;
use Webkul\Attribute\Models\Attribute;
use Illuminate\Support\Facades\DB;

class CleanWithoutPowerVariants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:clean-without-power';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove or update power-related attributes from product variants that are "Without Power" (lens_type = 39)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting update of "Without Power" product variants in the EAV table...');
        Log::info('CleanWithoutPowerVariants: Command started.');

        // Get all variants (child products) for processing.
        $variants = Product::whereNotNull('parent_id')->get();
        $updatedCount = 0;

        // Retrieve attribute IDs by querying the attributes table.
        $lensTypeId = Attribute::where('code', 'lens_type')->first()->id;
        $spherePowerId = Attribute::where('code', 'sphere_power')->first()->id;
        $twoDiffPowersId = Attribute::where('code', 'two_different_powers')->first()->id;
        $leftEyeId = Attribute::where('code', 'Sphere_Power_Left_Eye')->first()->id;
        $rightEyeId = Attribute::where('code', 'Sphere_Power_Right_Eye')->first()->id;

        Log::info('CleanWithoutPowerVariants: Retrieved attribute IDs', [
            'lensTypeId'      => $lensTypeId,
            'spherePowerId'   => $spherePowerId,
            'twoDiffPowersId' => $twoDiffPowersId,
            'leftEyeId'       => $leftEyeId,
            'rightEyeId'      => $rightEyeId,
        ]);

        foreach ($variants as $variant) {
            Log::info("Processing variant ID: {$variant->id}");
            // Fetch the lens_type value from the EAV table.
            $lensValue = DB::table('product_attribute_values')
                ->where('product_id', $variant->id)
                ->where('attribute_id', $lensTypeId)
                ->value('integer_value'); // assuming lens_type is stored in integer_value

            Log::info("Variant ID {$variant->id} - Lens Type Value: {$lensValue}");

            if ($lensValue == 67) {
                // Log current power attribute values (optional)
                $currentPowerValues = DB::table('product_attribute_values')
                    ->where('product_id', $variant->id)
                    ->whereIn('attribute_id', [
                        $spherePowerId,
                        $twoDiffPowersId,
                        $leftEyeId,
                        $rightEyeId,
                    ])
                    ->get();
                Log::info("Variant ID {$variant->id} - Current power attribute values", $currentPowerValues->toArray());
            
                // Remove all power-related attribute rows completely
                DB::table('product_attribute_values')
                    ->where('product_id', $variant->id)
                    ->whereIn('attribute_id', [
                        $spherePowerId,
                        $twoDiffPowersId,
                        $leftEyeId,
                        $rightEyeId,
                    ])
                    ->delete();
            
                Log::info("Variant ID {$variant->id} - Power attribute rows removed.");
                $this->info("Removed power attributes for variant ID {$variant->id}");
                $updatedCount++;
            }            
        }

        $this->info("Update complete. Updated {$updatedCount} variant(s) in the EAV table.");
        Log::info("CleanWithoutPowerVariants: Command completed. Total variants updated: {$updatedCount}");

        return 0;
    }
}
