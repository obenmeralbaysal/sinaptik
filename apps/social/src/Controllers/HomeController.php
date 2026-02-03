<?php

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index()
    {
        $plans = \App\Models\Plan::allActive();
        $this->render('landing', ['plans' => $plans]);
    }

    public function privacyPolicy()
    {
        $this->render('privacy_policy');
    }

    public function termsOfUse()
    {
        $this->render('terms_of_use');
    }
}
