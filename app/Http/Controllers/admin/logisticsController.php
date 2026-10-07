<?php

namespace App\Http\Controllers\admin;

use App\Models\Brand;
use App\Models\Cms;
use App\Helper\Helper;
use App\Models\Testimonial;
use Illuminate\View\View;
use Illuminate\Http\Request;
use \Yajra\Datatables\Datatables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Models\LogisticProvider;


class logisticsController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    public function index()
    {
       $brands = LogisticProvider::all();
// dd($brands);
        return view('logistics.index',compact('brands'));
    }

    public function add(): View
    {
        return view('logistics.add');
    }


    public function save(Request $request)
{
    $validated = $request->validate([
        'name'       => ['required', 'string', 'max:255'],
        'status'     => ['required', 'integer'],
        'logo'       => ['required', 'image'],
        'email'      => ['required', 'email', 'max:255', 'unique:logistic_providers,email'],
        'password'   => ['required'],
        'api_key'    => ['required'],
        'api_secret' => ['required'],
    ]);

    $data = $validated;
    $data['password'] = bcrypt($validated['password']);

    if ($request->hasFile('logo')) {
        $file = $request->file('logo');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('brands'), $fileName);
        $data['logo'] = 'brands/' . $fileName;
    } else {
        $data['logo'] = 'brands/image.png';
    }

    LogisticProvider::create($data);

    return to_route('logistics')->withSuccess('Logistics provider added successfully!');
}



//     public function save(Request $request)
// {
// // dd($request);
//     $validated = $request->validate([
//         'name'       => ['required', 'string', 'max:255'],
//         'status'     => ['required', 'integer'],
//         'logo'       => ['required', 'image'],
//         'email'      => ['required', 'email', 'max:255', 'unique:logistic_providers,email'],
//         'password'   => ['required'],
//         'api_key'    => ['required'],
//         'api_secret' => ['required'],
//     ]);

//     // Hash password and upload logo
//     $data = $validated;
//     $data['password'] = bcrypt($validated['password']); // Always hash passwords!

//     if ($request->hasFile('logo')) {
//         $data['logo'] = Helper::saveFile($request->file('logo'), 'brands');
//     } else {
//         $data['logo'] = 'brands/image.png'; 
//     }

//     // Save to DB
//     LogisticProvider::create($data);

//     return to_route('logistics')->withSuccess('Logistics provider added successfully!');
// }


    // public function save(Request $request): RedirectResponse
    // {
    //     $validated = $request->validate([
    //       'name'        => ['required'],
    //         'status'        => ['required', 'integer'],
    //         'logo'        => ['required'],
    //         'email'        => ['required'],
    //         'password'        => ['required'],
    //         'api_key'        => ['required'],
    //         'api_secret'        => ['required'],

    //     ]);

    //     $data = [...$validated, 'logo' => 'brands/image.png'];
    //     if ($request->file('logo')) {
    //         $data['logo'] = Helper::saveFile($request->file('logo'), 'brands');
    //     }

    //     LogisticProvider::create($data);
    //     return to_route('logistics')->withSuccess('logistics Added Successfully..!!');
    // }

    public function edit($id): View|RedirectResponse
    {
        $cms = Brand::find($id);
        if (!$cms) {
            return to_route('brands')->withError('Brands Not Found..!!');
        }
        return view('brand.edit', compact('cms'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $cms = Brand::find($id);
        if (!$cms) {
            return to_route('brands')->withError('Brands Not Found..!!');
        }

        $data = $request->validate([

            'status'        => ['required', 'integer'],
            'image'         => ['image', 'mimes:jpg,png,jpeg', 'max:5048']
        ]);

        if ($request->file('image')) {
            Helper::deleteFile($cms->image);
            $data['image'] = Helper::saveFile($request->file('image'), 'brands');
        }

        $cms->update($data);
        return to_route('brands')->withSuccess('Brands Updated Successfully..!!');
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Brand(), $request->id);
    }



        public function status(LogisticProvider $id, $status)
    {
        //dd($id);
        if ($id) {
            $id->status = $status;
            $id->save();
            return redirect()->back()
                ->with('success', 'Status successfully updated');
        }
    }


    public function destroy(LogisticProvider $id)
{
    if ($id) {
        $id->delete();
        return redirect()->back()->with('success', 'Logistic provider deleted successfully.');
    }

    return redirect()->back()->with('error', 'Something went wrong.');
}

}
