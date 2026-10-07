<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Blogs;
use App\Models\Brand;
use App\Models\Career;
use App\Models\Client;

use App\Models\LandingBrand;
use Illuminate\Http\Request;

use App\Models\Testimonial;

class HomeController extends Controller
{
public function index(Request $request)
{
    // If the request comes from admin dashboard route
    if ($request->is('admin') || $request->is('admin/*')) {
        // Example: Fetch counts/statistics for admin dashboard
        $totalUsers = \App\Models\User::count();
        $totalOrders = \App\Models\Order::count();
        $totalProducts = \App\Models\Product::count();

        return view('backend.dashboard.index', compact('totalUsers', 'totalOrders', 'totalProducts'));
    }

    // Otherwise, it's the frontend homepage
    $testimonials   = \App\Models\Testimonial::where('status', 1)->latest()->get();
    $brands         = \App\Models\Brand::where('status', 1)->latest()->get();
    $landingbrands  = \App\Models\LandingBrand::where('status', 1)->latest()->get();
    $careers        = \App\Models\Career::where('status', 1)->latest()->get();

    return view('frontend.website.pages.index', compact('testimonials', 'brands', 'landingbrands', 'careers'));
}

    public function cancelshipment(Request $request)
    {
        $testimonials = Testimonial::where('status', 1)->latest()->get();
        $brands = Brand::where('status', 1)->latest()->get();
        $landingbrands = LandingBrand::where('status', 1)->latest()->get();
        $careers = Career::where('status', 1)->latest()->get();

        return view('frontend.website.pages.shipmentcancel.index', compact('testimonials', 'brands', 'landingbrands','careers'));
    }
}
