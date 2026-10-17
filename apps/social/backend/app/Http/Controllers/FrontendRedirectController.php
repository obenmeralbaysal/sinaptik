<?php

namespace App\Http\Controllers;

class FrontendRedirectController extends Controller
{
    public function __invoke()
    {
        return redirect(rtrim((string) config('sinsocial.frontend_url'), '/'));
    }
}
