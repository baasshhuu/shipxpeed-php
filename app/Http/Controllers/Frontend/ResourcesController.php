<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\RefundPolicy;
use App\Models\TermsAndCondition;
use Illuminate\Http\Request;
use App\Models\Testimonial;
use App\Models\PrivacyPolicy;

class ResourcesController extends Controller
{
    public function index(Request $request)
    {
        $testimonials = Testimonial::where('status', 1)->latest()->get();


        return view('frontend.website.pages.index', compact('testimonials'));
    }
    public function privacypolicy(Request $request)
    {

        $privacy = PrivacyPolicy::where('status', 1)->latest()->first();

        return view('frontend.website.pages.privacypolicy', compact('privacy'));
    }
    public function refundpolicy(Request $request)
    {

        $refund = RefundPolicy::where('status', 1)->latest()->first();

        return view('frontend.website.pages.refundpolicy', compact('refund'));
    }

    public function termsandcondition(Request $request)
    {

        $term = TermsAndCondition::where('status', 1)->latest()->first();

        return view('frontend.website.pages.termsandcondition', compact('term'));
    }
}
