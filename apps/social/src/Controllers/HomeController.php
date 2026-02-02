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
}
