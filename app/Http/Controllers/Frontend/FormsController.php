<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\GetInTouch;
use App\Models\Inquiries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FormsController extends Controller
{
    public function sellerstore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email',
            'phone'   => 'required|digits:10',
            'service' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        GetInTouch::create([
            'name'    => $request->name,
            'email'   => $request->email,
            'phone'   => $request->phone,
            'service' => $request->service,
        ]);

        return redirect()->back()->with('success', 'Thanks for contacting us! We will get back to you soon.');
    }

    public function inquiries(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required',
            'email'        => 'required|email',
            'phone'        => 'required',
            'company_name' => 'required',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        Inquiries::create([
            'name'         => $request->name,
            'email'        => $request->email,
            'phone'        => $request->phone,
            'company_name' => $request->company_name,
        ]);

        return redirect()->back()->with('success', 'Thanks for contacting us! We will get back to you soon.');
    }
}
