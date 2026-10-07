<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Testimonial;

class FeaturesController extends Controller
{
    public function index(Request $request)
    {

        return view('frontend.website.pages.followup');
    }

    public function ltlshipments(Request $request)
    {

        return view('frontend.website.pages.ltlshipments');
    }

    public function hyperlocal(Request $request)
    {

        return view('frontend.website.pages.hyperlocal');
    }
}
