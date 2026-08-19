<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Redirection après connexion
     */
    protected $redirectTo = '/';

    /**
     * Constructeur
     */
    public function __construct()
    {
        // Syntaxe compatible Laravel 12
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
}