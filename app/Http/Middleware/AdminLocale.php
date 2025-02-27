<?php

namespace App\Http\Middleware;

use Closure;

class AdminLocale
{
    public function handle($request, Closure $next)
    {
        if ($request->session()->has('locale')) {
            $locale = $request->session()->get('locale');
            app()->setLocale($locale);
            config(['app.locale' => $locale]);
        }
    
        return $next($request);
    }    
}
