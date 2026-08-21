<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function showHomeForm()
    {
        session()->put(['title' => 'TRANG CHỦ']);

        return view('pages.home');
    }
}
