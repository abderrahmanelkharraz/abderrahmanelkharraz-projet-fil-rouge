<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
 
    public function index()
    {
        return view('home');
    }
 
    public function getAllUsers()
    {
        $users = User::where('role', '!=', 'admin')->get();

        return view('dashboard.users', ['users' => $users]);
    }
}


