<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    public function setLanguage(Request $request, $locale)
    {
        // Optionally, validate the locale against a list of supported languages.
        $supportedLocales = ['en', 'ar', 'tr']; // adjust as needed

        if (in_array($locale, $supportedLocales)) {
            $request->session()->put('admin_locale', $locale);
        }

        return redirect()->back();
    }
}
