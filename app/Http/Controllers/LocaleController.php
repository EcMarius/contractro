<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    /**
     * Switch the application locale.
     */
    public function switch(Request $request, string $locale)
    {
        $availableLocales = ['ro', 'en'];

        if (!in_array($locale, $availableLocales)) {
            abort(400, 'Invalid locale');
        }

        Session::put('locale', $locale);

        return redirect()->back();
    }
}
