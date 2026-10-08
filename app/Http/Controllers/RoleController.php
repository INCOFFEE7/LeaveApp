<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function switch($role)
    {
        session(['active_role' => $role]);
        return redirect()->route('landing'); // 👈 kembali ke landing
    }
}
