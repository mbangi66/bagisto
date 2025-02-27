<?php

namespace Webkul\Product\Observers;

use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductVariantObserver
{
    /**
     * Handle the Product "created" event.
     *
     * @param  \Webkul\Product\Models\Product  $product
     * @return void
     */

    // public function saved(Product $product)
    // {
    //     Log::debug("ProductVariantObserver saved fired for product: {$product->id}");
        
    //     if ($product->parent_id) {
    //         // Force reload the attribute_values relation if not already loaded
    //         $product->loadMissing('attribute_values');

    //         // Get the lens_type attribute value from the attribute_values relation
    //         $lensAttrValue = $product->attribute_values()
    //             ->whereHas('attribute', function($q) {
    //                 $q->where('code', 'lens_type');
    //             })
    //             ->first();

    //         // For select attributes, the value is stored in integer_value
    //         $lensType = $lensAttrValue ? $lensAttrValue->integer_value : null;

    //         Log::debug("Variant {$product->id} lens_type from attribute_values: " . print_r($lensType, true));

    //         if ($lensType == 39) { // 39 represents "Without Power"
    //             Log::debug("Variant {$product->id} is Without Power; removing power attributes.");
    //             $product->update([
    //                 'sphere_power'          => null,
    //                 'two_different_powers'  => null,
    //                 'Sphere_Power_Left_Eye' => null,
    //                 'Sphere_Power_Right_Eye'=> null,
    //             ]);
    //         }
    //     }
    // }

}
