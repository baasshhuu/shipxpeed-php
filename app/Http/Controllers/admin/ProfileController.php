<?php

namespace App\Http\Controllers\admin;

use App\Models\State;
use App\Helper\Helper;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

class ProfileController extends Controller
{
    protected $route;
    protected $role;
    protected $user_type;

    public function __construct(Request $request)
    {
        $gaurd      = $this->route = Helper::getGuardFromURL($request);
        $this->middleware(['auth:' .  $gaurd, function ($request, $next) use ($gaurd) {
            $this->role = ucfirst($gaurd);
            if ($gaurd == "web") {
                $this->role =   "Admin";
            }

            switch ($gaurd) {

                default:
                    $user_type = 0;
                    break;
            }

            $this->user_type = $user_type;
            return $next($request);
        }]);
    }

    public function edit(Request $request): View
    {
        $user = auth($this->route)->user();
        if (!$user) {
            return to_route($this->route . '/dashboard')->withError('Path not Valid.');
        }

        $role           = $this->role;
        $user['route']  = $this->route;
        $states         = State::active()->get();

        return view('profile.update', compact('user', 'role', 'states'));
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user           = auth($this->route)->user();
        $user->fill($request->validated());

        if ($request->hasFile('image')) {
            Helper::deleteFile($user->image);
            $user->image = Helper::saveFile($request->file('image'), 'admin');
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
        return back()->withSuccess('Profile Upated Successfully..!!');
    }

    public function update_password(Request $request): RedirectResponse
    {
        $request->validate([
            'old_password'  => ['required', 'string', 'max:100'],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user($this->route);
        if (Hash::check($request->old_password, $user->password)) {
            $user->update(['password' => Hash::make($request['password'])]);
            $this->logout($request);
            return to_route('loginPage', $this->route == 'web' ? 'admin' : $this->route)->withSuccess('Password updated successfully..!! Please login again.');
        }

        return back()->withError('Credentials not Valid.');
    }

    public function upload_image(Request $request): JsonResponse
    {
        $validation = Validator::make($request->all(), [
            'image'     => ['image', 'mimes:jpg,png,jpeg', 'max:2048'],
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status'   => false,
                'message'   => "Allow Types : .jpg, .png, .jpeg and Max Size : 2MB ",
                'image'     => ''
            ]);
        } else {

            $user = auth($this->route)->user();
            if ($request->file('image')) {
                Helper::deleteFile($user->image);
                $user->image = Helper::saveFile($request->file('image'), 'admin');
                $user->save();
            }

            return response()->json([
                'status'   => true,
                'message'   => 'Image Updated Successfully',
                'image'     => asset('storage/' . $user->image)
            ]);
        }
    }

    public function lock(Request $request): View|RedirectResponse
    {
        // only if user is logged in
        if (auth($this->route)->check()) {
            Session::put('locked', true);
            $user = auth($this->route)->user();
            $path = $this->route == "web" ? '' : $this->route;
            return view('profile.lockscreen', compact('user', 'path'));
        }
        return redirect('/login');
    }

    public function unlock(Request $request): RedirectResponse
    {
        // if user in not logged in
        if (!auth($this->route)->check())
            return redirect('/login');

        $password = $request->password;
        if (Hash::check($password, auth($this->route)->user()->password)) {
            Session::forget('locked');
            return redirect(($this->route == 'web' ? '' : $this->route) . '/dashboard')->withSuccess("Profile Unlocked Sussessfully.");
        } else {
            return redirect(($this->route == 'web' ? '' : $this->route) . '/lock')->withError("Invalid Password.");
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        return AuthenticatedSessionController::destroy($request);
    }
}
