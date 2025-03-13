<?php

/**
 * Store front routes.
 */
require 'store-front-routes.php';

/**
 * Customer routes. All routes related to customer
 * in storefront will be placed here.
 */
require 'customer-routes.php';

/**
 * Checkout routes. All routes related to checkout like
 * cart, coupons, etc will be placed here.
 */
require 'checkout-routes.php';

Route::get('/product/test-image-medium', function () {
    $path = public_path('storage/product/7/qe5zP74zEvx1X9q50k2B7Bv3PDnMJhN58s4Ymiy3.webp');
    if (! file_exists($path)) {
        dd("File does not exist: {$path}");
    }
    $image = Image::make($path);
    $filter = new \Webkul\Shop\CacheFilters\Medium;
    $filteredImage = $image->filter($filter);
    return $filteredImage->response('jpg');
});
