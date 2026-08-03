<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('frontend.home');
    }

    public function dashboard(): View
    {
        return view('frontend.dashboard');
    }
}