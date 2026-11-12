<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate(['locale' => ['required', 'string', Rule::in(['en', 'bn'])]]);
        $request->session()->put('locale', $data['locale']);
        $previous = url()->previous();
        $origin = $request->getSchemeAndHttpHost();
        $destination = str_starts_with($previous, $origin.'/') ? $previous : url('/');

        return redirect()->to($destination)->withCookie(cookie(
            'locale', $data['locale'], 525600, '/', null, $request->isSecure(), true, false, 'lax'
        ));
    }
}
