<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use App\Providers\RouteServiceProvider;
use Illuminate\Validation\ValidationException;

class GitTestController extends Controller
{
    public function index(Request $request, $guard = 'web'): View
    {

        return view('gittest');
    }


}
