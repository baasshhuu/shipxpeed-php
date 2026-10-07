<?php

namespace App\Http\Controllers\Frontend;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\BeyondWork;
use App\Models\Career;
use App\Models\CareerDetail;
use App\Models\TeamSpirit;
use App\Models\WorkCulture;
use Illuminate\Http\Request;
use App\Models\Testimonial;

class CompanyController extends Controller
{
    public function about(Request $request)
    {

        return view('frontend.website.pages.about');
    }
    public function contact(Request $request)
    {

        return view('frontend.website.pages.contact');
    }
    public function lifeshipxpeed(Request $request)
    {
        $workculture = WorkCulture::where('status', 1)->latest()->first();
        $teamspirit = TeamSpirit::where('status', 1)->latest()->first();
        $beyondworks = BeyondWork::where('status', 1)->latest()->get();
        return view('frontend.website.pages.lifeshipxpeed', compact('workculture', 'teamspirit', 'beyondworks'));
    }




    public function ratecalculater(Request $request)
    {

        return view('frontend.website.pages.ratecalculater');
    }

    public function price(Request $request)
    {

        return view('frontend.website.pages.price');
    }

    public function trackorder(Request $request)
    {

        return view('frontend.website.pages.trackorder');
    }

    
    public function career(Request $request)
    {
        $careers = Career::where('status', 1)->latest()->get();
        return view('frontend.website.pages.career', compact('careers'));
    }

    public function storeCareerDetails(Request $request)
    {
        $request->validate([
            'title_id' => 'required|exists:careers,id',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mobilenumber' => 'required|string|max:15',
            'address' => 'required|string',
            'resume' => 'required|file|mimes:pdf,doc,docx|max:2048',
        ]);

        $resumePath = Helper::saveFile($request->file('resume'), 'resumes');

        CareerDetail::create([
            'title_id' => $request->title_id,
            'full_name' => $request->full_name,
            'email' => $request->email,
            'mobilenumber' => $request->mobilenumber,
            'address' => $request->address,
            'resume' => $resumePath,
        ]);

        return back()->with('success', 'Your application has been submitted successfully!');
    }
}
