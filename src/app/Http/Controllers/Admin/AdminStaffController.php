<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class AdminStaffController extends Controller
{
    public function index()
    {
        $staffs = User::where('role', User::ROLE_STAFF)
            ->get();

        return view('admin.staffs.index', compact('staffs'));
    }
}
