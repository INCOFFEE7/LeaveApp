<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function index()
    {
        $roles = auth()->user()?->roles ?? collect(); // Default: empty collection kalau belum login
        return view('landing', compact('roles'));
    }
}
