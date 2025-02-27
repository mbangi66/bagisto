<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// routes/web.php (only for local debugging)
if (app()->environment('local')) {
    Route::get('/debug/phpinfo', function () {
        ob_start();
        phpinfo(INFO_MODULES);
        $phpinfo = ob_get_clean();
        $plainInfo = strip_tags($phpinfo);
        Log::info("PHP Info: " . $plainInfo);
        return "PHP info logged.";
    });
}


