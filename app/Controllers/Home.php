<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function login()
    {
        if ($redirect = $this->isLoggedUser()) return $redirect;
        return view('login');
    }

    public function register()
    {
        if ($redirect = $this->isLoggedUser()) return $redirect;
        return view('register');
    }

    public function dashboard()
    {
        if ($redirect = $this->isAuthUser()) return $redirect;
        return view('dashboard');
    }

    public function wallet()
    {
        if ($redirect = $this->isAuthUser()) return $redirect;
        return view('wallet');
    }

}
